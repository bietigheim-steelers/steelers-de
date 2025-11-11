const prices = {
  plus: {
    "A,G": {
      vollzahler: 785,
      ermaessigt: 677,
      jugendlich: 465,
      kind: 392,
      behinderung: 392,
    },
    "B,F,H,L": {
      vollzahler: 680,
      ermaessigt: 572,
      jugendlich: 418,
      kind: 334,
      behinderung: 334,
    },
    "C,I,K": {
      vollzahler: 575,
      ermaessigt: 502,
      jugendlich: 337,
      kind: 287,
      behinderung: 287,
      familie1: 855,
      familie2: 1133,
      familie3: 1296,
    },
    J: {
      vollzahler: 418,
      ermaessigt: 370,
      jugendlich: 263,
      kind: 203,
      behinderung: 203,
    },
    "R1,R3,R4": {
      rollstuhl: 360,
    },
  },
  basic: {
    "A,G": {
      vollzahler: 686,
      ermaessigt: 592,
      jugendlich: 405,
      kind: 343,
      behinderung: 343,
    },
    "B,F,H,L": {
      vollzahler: 592,
      ermaessigt: 499,
      jugendlich: 364,
      kind: 291,
      behinderung: 291,
    },
    "C,I,K": {
      vollzahler: 499,
      ermaessigt: 436,
      jugendlich: 291,
      kind: 249,
      behinderung: 249,
      familie1: 748,
      familie2: 998,
      familie3: 1144,
    },
    J: {
      vollzahler: 364,
      ermaessigt: 322,
      jugendlich: 129.74,
      kind: 129.74,
      behinderung: 176,
    },
    "R1,R3,R4": {
      rollstuhl: 312,
    },
  },
};

export const ticketPrice = (type, category, area, ticket_block) => {
  if (!type || !prices[type]) {
    return 0; // Return 0 if the type is not found
  }
  let cat = category;
  if (["rentner", "student", "azubi", "schueler", "mitglied"].includes(cat)) {
    cat = "ermaessigt";
  }
  if (area === "stehplatz") {
    return prices[type]["J"][cat];
  } else if (area === "rollstuhl") {
    return prices[type]["R1,R3,R4"]["rollstuhl"];
  } else if (ticket_block) {
    let block = ticket_block.slice(-1);
    switch (block) {
      case "A":
      case "G":
        block = "A,G";
        break;
      case "B":
      case "F":
      case "H":
      case "L":
        block = "B,F,H,L";
        break;
      case "C":
      case "I":
      case "K":
        block = "C,I,K";
    }
    return prices[type][block][cat];
  }
  return 0;
};

export const ticketPriceFormatted = (type, category, area, ticket_block) => {
  const price = ticketPrice(type, category, area, ticket_block);
  return new Intl.NumberFormat("de-DE", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price);
};

export const getLowestPrice = (type, category = null, area = null) => {
  let lowestPrice = Infinity;
  if (!prices[type]) {
    return 0; // Return 0 if the type is not found
  }

  Object.entries(prices[type]).forEach(([blockKey, categories]) => {
    if (area === "stehplatz" && blockKey === "J") {
      if (category) {
        lowestPrice = Math.min(lowestPrice, categories[category] || 0);
      } else {
        Object.values(categories).forEach((price) => {
          lowestPrice = Math.min(lowestPrice, price || 0);
        });
      }
    } else if (area === "rollstuhl" && blockKey === "R1,R3,R4") {
      lowestPrice = Math.min(lowestPrice, categories["rollstuhl"] || 0);
    } else if (
      area === "sitzplatz" &&
      ["A,G", "B,F,H,L", "C,I,K"].includes(blockKey)
    ) {
      if (category) {
        lowestPrice = Math.min(lowestPrice, categories[category] || 0);
      } else {
        Object.values(categories).forEach((price) => {
          lowestPrice = Math.min(lowestPrice, price || 0);
        });
      }
    } else if (!area || blockKey.includes(area)) {
      if (category) {
        lowestPrice = Math.min(lowestPrice, categories[category] || 0);
      } else {
        Object.values(categories).forEach((price) => {
          lowestPrice = Math.min(lowestPrice, price || 0);
        });
      }
    }
  });

  return lowestPrice === Infinity ? 0 : lowestPrice;
};
