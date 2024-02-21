<script>
$(document).ready(function () {

    //Initialize Select2 Elements
    // $('.select2').select2();

    let audit_logs = $('#audit_log_table').DataTable({
        processing: true,
        serverSide: true,
        "ajax": {
            "url": "/audit-logs",
            "data": function (d) {
                // d.product_id = $('#product_id').val();
                // d.serial_number_id = $('#serial_number_id').val();
                // d.status = $('#status').val();
            }
        },
        columnDefs: [{
            "orderable": false,
            "searchable": false
        }],
        aaSorting: [0, 'desc'],
        columns: [
            {
                data: "description",
                name: "description"
            },
            {
                data: "subject_id",
                name: "subject_id"
            },
            {
                data: "subject_type",
                name: "subject_type"
            },
            {
                data: "user",
                name: "user"
            },
            // {
            //     data: "properties",
            //     name: "properties"
            // },
            {
                data: "host",
                name: "host"
            },
            {
                data: "created_at",
                name: "created_at"
            },
            {
                data: "action",
                name: "action"
            }
        ]
    });

    $(document).on('change', '#product_id, #serial_number_id, #status', function () {
        audit_logs.ajax.reload();
    })

});
</script>