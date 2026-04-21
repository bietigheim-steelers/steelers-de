const prices = {
  plus: {
    "A,G": {
      vollzahler: 855,
      ermaessigt: 735,
      jugendlich: 510,
      kind: 426,
      behinderung: 426,
    },
    "B,F,H,L": {
      vollzahler: 737,
      ermaessigt: 618,
      jugendlich: 453,
      kind: 369,
      behinderung: 369,
    },
    "C,I,K": {
      vollzahler: 621,
      ermaessigt: 537,
      jugendlich: 360,
      kind: 310,
      behinderung: 310,
      familie1: 948,
      familie2: 1249,
      familie3: 1435,
    },
    J: {
      vollzahler: 430,
      ermaessigt: 381,
      jugendlich: 275,
      kind: 215,
      behinderung: 215,
    },
    "R1,R3,R4": {
      rollstuhl: 383,
    },
  },
  basic: {
    "A,G": {
      vollzahler: 749,
      ermaessigt: 645,
      jugendlich: 447,
      kind: 375,
      behinderung: 375,
    },
    "B,F,H,L": {
      vollzahler: 645,
      ermaessigt: 540,
      jugendlich: 395,
      kind: 322,
      behinderung: 322,
    },
    "C,I,K": {
      vollzahler: 540,
      ermaessigt: 468,
      jugendlich: 312,
      kind: 270,
      behinderung: 270,
      familie1: 832,
      familie2: 1102,
      familie3: 1268,
    },
    J: {
      vollzahler: 374,
      ermaessigt: 333,
      jugendlich: 129.74,
      kind: 129.74,
      behinderung: 187,
    },
    "R1,R3,R4": {
      rollstuhl: 333,
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
