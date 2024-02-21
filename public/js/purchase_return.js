$(document).ready(function() {
    //get suppliers
    $('#supplier_id').select2({
        ajax: {
            url: '/purchases/get_suppliers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        minimumInputLength: 1,
        escapeMarkup: function(m) {
            return m;
        },
        templateResult: function(data) {
            if (!data.id) {
                return data.text;
            }
            var html = data.text + ' - ' + data.business_name + ' (' + data.contact_id + ')';
            return html;
        }
    });
    //Add products
    if ($('#search_product_for_purchase_return').length > 0) {
        //Add Product
        $('#search_product_for_purchase_return')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        purchase_return_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_purchase_return').removeAttr('disabled');
        } else {
            $('#search_product_for_purchase_return').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
    });

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    $('form#purchase_return_form').validate();

    $(document).on('click', 'button#submit_purchase_return_form', function(e) {
        e.preventDefault();

        //Check if product is present or not.
        if ($('table#purchase_return_product_table tbody tr').length <= 0) {
            toastr.warning(LANG.no_products_added);
            $('input#search_product_for_purchase_return').select();
            return false;
        }

        if ($('form#purchase_return_form').valid()) {
            $('form#purchase_return_form').submit();
        }
    });

    $('#tax_id').change(function() {
        update_table_total();
    });

    $('#purchase_return_product_table tbody')
    .find('.expiry_datepicker')
    .each(function() {
        $(this).datepicker({
            autoclose: true,
            format: datepicker_date_format,
        });
    });

    // get suppliers purchase return invoices
    $(document).on('change', '#supplier_id', function () {
        let supplier_id = $(this).val();
        $.ajax({
            method: 'GET',
            url: '/purchase-return/get/suppliers/purchase-invoice/' + supplier_id,
            success: function (res) {
                $('#purchase_invoice_ids').empty().html(res);
            },
        });
    });

    // add purchase to table
    $(document).on('change', '#purchase_invoice_ids', function () {
        let selected_invoice_ids = $(this).val();
        $.ajax({
            method: 'GET',
            url: '/purchase-return/get/purchases/',
            data: {
                invoice_ids: selected_invoice_ids,
            },
            success: function (res) {
                $('#purchase_invoice_table_tbody').empty().html(res);
            },
        });
    });

    // edit purchase table
    $(document).on('change', '#purchase_invoice_edit_ids', function () {
        let selected_invoice_ids = $(this).val();
        let newly_added_invoices_ids =  [selected_invoice_ids[selected_invoice_ids.length - 1]];
        $.ajax({
            method: 'GET',
            url: '/purchase-return/get/purchases/',
            data: {
                'invoice_ids': newly_added_invoices_ids
            },
            success: function (res) {
                $('#purchase_invoice_table_tbody').append(res);
            }
        });
    });

    // get value when type
    $(document).on('input', '.purchase_invoice_pay_amount', function () {
        let row_id = $(this).attr('id');
        let row_purchase_value = $('#final_total' + row_id).val();
        let row_input_value = $(this).val();
        calculate_purchase_payment_amount();
        checkTotalAmountAndTotalReturnAmountValidation(row_purchase_value, row_input_value);
    });

    // remove row
    $(document).on('click', '.remove_purchase_row', function () {
        this.closest('tr').remove();
        calculate_purchase_payment_amount();
    })
    calculate_purchase_payment_amount();
});

function purchase_return_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/purchase-return/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id },
        dataType: 'html',
        success: function(result) {
            $('table#purchase_return_product_table tbody').append(result);
            
            $('table#purchase_return_product_table tbody tr:last').find('.expiry_datepicker').datepicker({
                autoclose: true,
                format: datepicker_date_format,
            });
            
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_table_total() {
    var table_total = 0;
    $('table#purchase_return_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });
    var tax_rate = parseFloat($('option:selected', $('#tax_id')).data('tax_amount'));
    var tax = __calculate_amount('percentage', tax_rate, table_total);
    __write_number($('input#tax_amount'), tax);
    var final_total = table_total + tax;
    $('input#total_amount').val(final_total);
    $('span#total_return').text(__number_f(final_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_adjustment_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/stock-adjustments/' + rowData.DT_RowId,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

function calculate_purchase_payment_amount() {
    let total_pay_amount = 0;
    let final_total_value = $('#total_amount').val();
    $('#purchase_invoice_table tbody .purchase_invoice_pay_amount').each(function () {
        let pay_amount = $(this).val();
        if (pay_amount === '') {
            pay_amount = 0;
        }
        total_pay_amount += parseFloat(pay_amount);
    });
    $('#total_purchase_amount_display').text(__number_f(total_pay_amount));
    $('#total_purchase_amount').val(total_pay_amount);
    let available_value = final_total_value - total_pay_amount;
    $('#total_available_amount_display').text(__number_f(available_value));
}

function checkTotalAmountAndTotalReturnAmountValidation(row_purchase_value, row_input_value) {
    let final_total_value = $('#total_amount').val();
    let total_purchase_amount_display_value = $('#total_purchase_amount').val();
    // alert(total_return_value);
    // alert(total_return_amount_display_value)
    if (parseFloat(final_total_value) >= parseFloat(total_purchase_amount_display_value)) {
        // alert('yes')
        $('#submit_purchase_return_form').attr('disabled', false);
        checkInputValueValidation(row_purchase_value, row_input_value);
    } else {
        // alert('no')
        $('#submit_purchase_return_form').attr('disabled', true);
    }
}

function checkInputValueValidation(row_purchase_value, row_input_value) {
    if (parseFloat(row_purchase_value) >= parseFloat(row_input_value)){
        // alert('yes')
        $('#submit_purchase_return_form').attr('disabled', false);
    }else {
        // alert('no')
        $('#submit_purchase_return_form').attr('disabled', true);
    }
}
