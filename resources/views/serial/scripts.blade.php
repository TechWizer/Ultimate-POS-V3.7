<script>
$(document).ready(function () {

    //Initialize Select2 Elements
    $('.select2').select2();

    let serials_table = $('#serials_table').DataTable({
        processing: true,
        serverSide: true,
        "ajax": {
            "url": "/serials",
            "data": function (d) {
                d.product_id = $('#product_id').val();
                d.serial_number_id = $('#serial_number_id').val();
                d.status = $('#status').val();
            }
        },
        columnDefs: [{
            "orderable": false,
            "searchable": false
        }],
        aaSorting: [0, 'asc'],
        columns: [
            {
                data: "product_name",
                name: "product_name"
            },
            {
                data: "serial_number",
                name: "serial_number"
            },
            {
                data: "status",
                name: "status"
            },
            {
                data: "purchase_ref_no_invoice_no",
                name: "purchase_ref_no_invoice_no"
            },
            // {
            //     data: "invoice_no",
            //     name: "invoice_no"
            // }
        ]
    });

    $(document).on('change', '#product_id, #serial_number_id, #status', function () {
        serials_table.ajax.reload();
    })

});
</script>