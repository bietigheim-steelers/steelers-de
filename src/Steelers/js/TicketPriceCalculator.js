const prices = {
  "plus": {
    "A,G": {
      "vollzahler": 720,
      "ermaessigt": 624,
      "jugendlich": 432,
      "kind": 360,
      "behinderung": 360
    },
    "B,F,H,L": {
      "vollzahler": 624,
      "ermaessigt": 528,
      "jugendlich": 384,
      "kind": 312,
      "behinderung": 312
    },
    "C,I,K": {
      "vollzahler": 528,
      "ermaessigt": 456,
      "jugendlich": 312,
      "kind": 264,
      "behinderung": 264,
      "familie1": 792,
      "familie2": 1056,
      "familie3": 1200
    },
    "J": {
      "vollzahler": 384,
      "ermaessigt": 336,
      "jugendlich": 240,
      "kind": 192,
      "behinderung": 192
    },
    "R1,R3,R4": {
      "rollstuhl": 336
    }
  },
  "basic": {
    "A,G": {
      "vollzahler": 570,
      "ermaessigt": 494,
      "jugendlich": 342,
      "kind": 285,
      "behinderung": 285,
    },
    "B,F,H,L": {
      "vollzahler": 494,
      "ermaessigt": 418,
      "jugendlich": 304,
      "kind": 247,
      "behinderung": 247
    },
    "C,I,K": {
      "vollzahler": 418,
      "ermaessigt": 361,
      "jugendlich": 247,
      "kind": 209,
      "behinderung": 209,
      "familie1": 627,
      "familie2": 836,
      "familie3": 950
    },
    "J": {
      "vollzahler": 304,
      "ermaessigt": 266,
      "jugendlich": 190,
      "kind": 152,
      "behinderung": 152
    },
    "R1,R3,R4": {
      "rollstuhl": 266
    }
  }
}

export const ticketPrice = (type, category, area, ticket_block) => {
  if(!type || !prices[type]) {
    return 0; // Return 0 if the type is not found
  }
      let cat = category
      if(['rentner', 'student', 'azubi', 'schueler', 'mitglied'].includes(cat)) {
        cat = 'ermaessigt'
      }
      if (area === 'stehplatz') {
        return prices[type]['J'][cat]
      } else if (area === 'rollstuhl') {
        return prices[type]['R1,R3,R4']['rollstuhl']
      } else if (ticket_block) {
        let block = ticket_block.slice(-1)
        switch(block) {
          case 'A':
          case 'G':
            block = 'A,G'
            break
          case 'B':
          case 'F':
          case 'H':
          case 'L':
            block = 'B,F,H,L'
            break
          case 'C':
          case 'I':
          case 'K':
            block = 'C,I,K'
        }
        return prices[type][block][cat]
      }
      return 0;
};

export const ticketPriceFormatted = (type, category, area, ticket_block) => {
  const price = ticketPrice(type, category, area, ticket_block);
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price);
}

export const getLowestPrice = (type, category = null, area = null) => {
  let lowestPrice = Infinity;
  if (!prices[type]) {
    return 0; // Return 0 if the type is not found
  }

  Object.entries(prices[type]).forEach(([blockKey, categories]) => {
    if (area === 'stehplatz' && blockKey === 'J') {
      if (category) {
        lowestPrice = Math.min(lowestPrice, categories[category] || 0);
      } else {
        Object.values(categories).forEach(price => {
          lowestPrice = Math.min(lowestPrice, price || 0);
        });
      }
    } else if (area === 'rollstuhl' && blockKey === 'R1,R3,R4') {
      lowestPrice = Math.min(lowestPrice, categories['rollstuhl'] || 0);
    } else if (area === 'sitzplatz' && ['A,G', 'B,F,H,L', 'C,I,K'].includes(blockKey)) {
      if (category) {
        lowestPrice = Math.min(lowestPrice, categories[category] || 0);
      } else {
        Object.values(categories).forEach(price => {
          lowestPrice = Math.min(lowestPrice, price || 0);
        });
      }
    } else if (!area || blockKey.includes(area)) {
      if (category) {
        lowestPrice = Math.min(lowestPrice, categories[category] || 0);
      } else {
        Object.values(categories).forEach(price => {
          lowestPrice = Math.min(lowestPrice, price || 0);
        });
      }
    }
  });

  return lowestPrice === Infinity ? 0 : lowestPrice;
};