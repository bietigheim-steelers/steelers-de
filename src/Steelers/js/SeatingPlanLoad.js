let isLoaded = false;
let data = {};
let loadPromise = null;

export const loadSeats = async () => {
  if (!isLoaded) {
    if (!loadPromise) {
      loadPromise = (async () => {
        try {
          const response = await fetch(
            "/files/steelers/tools/seatingPlan/booked_seats.json"
          );
          if (!response.ok) {
            console.error("Error loading booked seats");
            return [];
          }
          data = await response.json();
          isLoaded = true;
        } catch (error) {
          console.error("Error loading JSON:", error);
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
