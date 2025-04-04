let isLoaded = false;
let bookedSeats = [];
let loadPromise = null;

export const loadSeats = async () => {
  if (!isLoaded) {
    if (!loadPromise) {
      loadPromise = (async () => {
        try {
          const response = await fetch('/src/Steelers/js/booked_seats.json');
          if (!response.ok) {
            throw new Error(`Failed to load JSON: ${response.statusText}`);
          }
          bookedSeats = await response.json();
          isLoaded = true;
        } catch (error) {
          console.error('Error loading JSON:', error);
          throw error;
        } finally {
          loadPromise = null;
        }
      })();
    }
    await loadPromise;
  }
  return bookedSeats;
};