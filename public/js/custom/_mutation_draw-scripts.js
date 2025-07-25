Livewire.on("showError", function (message) {
    if (window.Swal) {
        Swal.fire({
            icon: "error",
            title: "Gagal Proses Mutasi",
            text: message,
        });
    } else {
        alert("Gagal Proses Mutasi: " + message);
    }
});
