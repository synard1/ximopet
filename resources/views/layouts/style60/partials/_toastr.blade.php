<script>
    function showToastr(element) {
        var type = element.dataset.toastrType || 'success';
        var message = element.dataset.toastrMessage || 'Operation successful!';
        var title = element.dataset.toastrTitle || '';

        toastr.options = {
            closeButton: true,
            progressBar: true,
            // Add more options as needed
        };

        toastr[type](message, title);
    }

    // Trigger toastr on elements with the 'data-toastr' attribute
    $(document).ready(function() {
        $('[data-toastr]').each(function() {
            showToastr(this);
        });
    });
</script>