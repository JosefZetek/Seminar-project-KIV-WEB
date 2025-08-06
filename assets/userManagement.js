function addEventHandlersApproveButtons() {
    const approveButtons = document.querySelectorAll('.approve-button');

    approveButtons.forEach(button => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-id');

            console.log('Clicked button for item with id:', itemId);
        });
    });
}

function addEventHandlersRemoveButtons() {
    const approveButtons = document.querySelectorAll('.remove-button');

    approveButtons.forEach(button => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-id');

            console.log('Removing item with id:', itemId);
        });
    });
}

document.addEventListener("DOMContentLoaded", function () {
    addEventHandlersApproveButtons();
    addEventHandlersRemoveButtons();
});