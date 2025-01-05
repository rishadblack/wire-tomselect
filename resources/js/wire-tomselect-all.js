import TomSelect from "tom-select";

if (!TomSelect) {
  console.error(
    "TomSelect package is not installed. Please install it using npm or yarn."
  );
} else {
  window.TomSelect = TomSelect;
}

window.tom_select_set_value = {}; // Initialize an empty object to store merged data

Livewire.on("tom_select_set_value", (event) => {
  // Merge the incoming event data with the existing data
  event.forEach((item) => {
    Object.keys(item).forEach((key) => {
      // Merge or overwrite the existing key-value pair
      window.tom_select_set_value[key] = item[key];
    });
  });
});
