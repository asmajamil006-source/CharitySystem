function toggleOther() {
    let category = document.getElementById("category").value;
    let field = document.getElementById("otherField");

    if (field) {
        field.style.display = (category === "Other") ? "block" : "none";
    }
}