import TomSelect from "tom-select";

if (!TomSelect) {
    console.error(
        "TomSelect package is not installed. Please install it using npm or yarn."
    );
} else {
    window.TomSelect = TomSelect;
}
