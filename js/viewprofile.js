function confirmAndDelete(type) {
    let selectedItem = '';
    let url = 'Viewprofile.php?delete=';

    if (type === 'skill') {
        selectedItem = document.getElementById('skillDropdown').value;
    } else if (type === 'cv') {
        selectedItem = document.getElementById('cvDropdown').value;
    }

    if (!selectedItem) {
        alert('Please select an item to delete.');
        return;
    }

    const confirmation = confirm(`Are you sure you want to delete this ${type}?`);
    if (confirmation) {
        window.location.href = url + encodeURIComponent(selectedItem);
    }
}

function logout() {
    window.location.href = 'logout.php';
}

function toggleNotificationPopup() {
    const popup = document.getElementById('notificationPopup');
    popup.classList.toggle('active');
}