let isLoaded = false;
let data = {};
let loadPromise = null;

export const loadSeats = async () => {
  if (!isLoaded) {
    if (!loadPromise) {
      loadPromise = (async () => {
        try {
          const response = await fetch(
            "/seasontickets/api/seats?t=" + Date.now()
          );
          if (!response.ok) {
            console.error("Error loading booked seats");
            return { booked: [], reserved: [] };
          }
          data = await response.json();
          isLoaded = true;
        } catch (error) {
          console.error("Error loading seats from API:", error);
          throw error;
        } finally {
          loadPromise = null;
        }
      })();
    }
    await loadPromise;
  }
  return data;
};

export const resetSeats = () => {
  isLoaded = false;
  data = {};
  loadPromise = null;
};
