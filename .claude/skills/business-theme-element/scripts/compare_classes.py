#!/usr/bin/env python3
"""Vergleicht die class-Attribute eines gerenderten Abschnitts mit denen der Theme-HTML.

Der Sinn: das Theme-CSS ist ein fertiger Build, den wir nicht neu erzeugen koennen. Jede
abweichende oder weggelassene Klasse ist deshalb ein echter Fehler und keine Stilfrage.

Verglichen wird ueber difflib, nicht Position gegen Position. Das ist wichtig, weil Contao
zusaetzliche Wrapper einzieht (`content-business-timeline`, `mod_article block`, `rte`), die
im Theme nicht vorkommen — ein positionsweiser Vergleich waere danach um eine Stelle
verschoben und jede weitere Zeile falsch. Solche Zusaetze erscheinen hier als EXTRA und
gelten nicht als Fehler.

Whitespace wird normalisiert; das Theme enthaelt in vielen class-Attributen doppelte
Leerzeichen ohne Bedeutung.

Beispiel:

    python compare_classes.py \\
      --theme "…/main-files/about-us.html" \\
      --theme-marker "about-us-our-journey Start" --theme-end "about-us-our-journey end" \\
      --rendered /tmp/demo.html \\
      --rendered-marker "content-business-timeline" --rendered-end "mod_article block"

Exit-Code 1, sobald eine Klasse abweicht (DIFF) oder fehlt (FEHLT).
"""

from __future__ import annotations

import argparse
import difflib
import re
import sys

CLASS_RE = re.compile(r'class="([^"]*)"')
SVG_RE = re.compile(r"<svg.*?</svg>", re.S)


def normalise(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


def block(html: str, marker: str, end: str | None, window: int, *, strip_svg: bool, label: str) -> str:
    """Schneidet den Abschnitt zwischen Start- und Endmarker heraus.

    Die Marker sind schlichte Textsuchen: in der Theme-Datei die HTML-Kommentare des
    Abschnitts, im gerenderten HTML eine Klasse oder ein Attribut, die den Abschnitt
    eindeutig eroeffnen bzw. beenden. Ohne Endmarker wird `window` Zeichen weit gelesen —
    dann besser pruefen, ob am Ende Fremdmaterial mitkommt.
    """
    start = html.find(marker)

    if start < 0:
        sys.exit(f"{label}: Startmarker nicht gefunden: {marker!r}")

    if end:
        stop = html.find(end, start + len(marker))

        if stop < 0:
            sys.exit(f"{label}: Endmarker nicht gefunden: {end!r}")
    else:
        stop = start + window

    segment = html[start:stop]

    # Inline-SVGs tragen eigene class-Attribute, die den Vergleich verrauschen; die
    # Icon-Klassen selbst stehen ohnehin am umgebenden Element.
    return SVG_RE.sub("", segment) if strip_svg else segment


def classes(segment: str) -> list[str]:
    return [normalise(c) for c in CLASS_RE.findall(segment)]


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--theme", required=True, help="HTML-Datei des Themes")
    parser.add_argument("--theme-marker", required=True, help="Text, ab dem der Theme-Abschnitt beginnt")
    parser.add_argument("--theme-end", help="Text, an dem der Theme-Abschnitt endet (dringend empfohlen)")
    parser.add_argument("--rendered", required=True, help="Gerenderte Seite (z. B. per curl gespeichert)")
    parser.add_argument("--rendered-marker", required=True, help="Text, ab dem der gerenderte Abschnitt beginnt")
    parser.add_argument("--rendered-end", help="Text, an dem der gerenderte Abschnitt endet")
    parser.add_argument("--limit", type=int, default=30, help="Maximale Anzahl ausgegebener OK-Zeilen (verglichen wird immer alles)")
    parser.add_argument("--window", type=int, default=20000, help="Zeichen ab dem Startmarker, falls kein Endmarker gesetzt ist")
    parser.add_argument("--keep-svg", action="store_true", help="class-Attribute innerhalb von <svg> mitvergleichen")
    args = parser.parse_args()

    strip_svg = not args.keep_svg
    theme_html = open(args.theme, encoding="utf-8", errors="replace").read()
    rendered_html = open(args.rendered, encoding="utf-8", errors="replace").read()

    expected = classes(block(theme_html, args.theme_marker, args.theme_end, args.window, strip_svg=strip_svg, label="Theme"))
    actual = classes(block(rendered_html, args.rendered_marker, args.rendered_end, args.window, strip_svg=strip_svg, label="Rendered"))

    if not expected:
        sys.exit("Im Theme-Abschnitt wurden keine class-Attribute gefunden — Marker oder --window pruefen.")

    ok = diffs = order = missing = extra = 0
    shown_ok = 0

    for tag, i1, i2, j1, j2 in difflib.SequenceMatcher(None, expected, actual, autojunk=False).get_opcodes():
        if tag == "equal":
            for value in expected[i1:i2]:
                ok += 1
                shown_ok += 1
                if shown_ok <= args.limit:
                    print(f"OK    | {value}")
                elif shown_ok == args.limit + 1:
                    print("OK    | ... (weitere identische Zeilen ausgeblendet, --limit erhoehen)")
        elif tag == "replace":
            for theme_value, rendered_value in zip(expected[i1:i2], actual[j1:j2]):
                # Reihenfolge innerhalb eines class-Attributs ist fuer CSS bedeutungslos.
                # Als DIFF gemeldet wuerde sie den naechsten Durchlauf auf Phantomjagd
                # schicken — deshalb eigene Kategorie, die den Exit-Code nicht faerbt.
                if sorted(theme_value.split()) == sorted(rendered_value.split()):
                    order += 1
                    print(f"ORDER | {theme_value}")
                    print(f"      | rendered: {rendered_value}")
                else:
                    diffs += 1
                    print(f"DIFF  | {theme_value}")
                    print(f"      | rendered: {rendered_value}")
            # Ungleich lange Bloecke: Rest als fehlend bzw. zusaetzlich zaehlen
            span = min(i2 - i1, j2 - j1)
            for theme_value in expected[i1 + span : i2]:
                missing += 1
                print(f"FEHLT | {theme_value}")
            for rendered_value in actual[j1 + span : j2]:
                extra += 1
                print(f"EXTRA | {rendered_value}")
        elif tag == "delete":
            for theme_value in expected[i1:i2]:
                missing += 1
                print(f"FEHLT | {theme_value}")
        elif tag == "insert":
            for rendered_value in actual[j1:j2]:
                extra += 1
                print(f"EXTRA | {rendered_value}")

    print(
        f"\n{ok} identisch, {diffs} abweichend, {order} nur andere Reihenfolge, "
        f"{missing} fehlend, {extra} zusaetzlich (Contao-Wrapper sind hier normal)"
    )

    if not args.theme_end or not args.rendered_end:
        print("Hinweis: ohne --theme-end/--rendered-end wird ueber den Abschnitt hinaus gelesen; "
              "unerklaerliche FEHLT/EXTRA-Zeilen kommen meist daher.")

    return 1 if diffs or missing else 0


if __name__ == "__main__":
    raise SystemExit(main())
