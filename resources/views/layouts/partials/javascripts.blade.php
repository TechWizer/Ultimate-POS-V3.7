<script type="text/javascript">
    base_path = "{{url('/')}}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{config('broadcasting.connections.pusher.key')}}';
    APP.PUSHER_APP_CLUSTER = '{{config('broadcasting.connections.pusher.options.cluster')}}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{$__is_pusher_enabled}}';
    @auth
            @php
                $user = Auth::user();
            @endphp
        APP.USER_ID = "{{$user->id}}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->
<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>

@if(file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);

    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);

    $business_time_format = session('business.time_format');
    $moment_time_format = 'HH:mm';
    if($business_time_format == 12){
        $moment_time_format = 'hh:mm A';
    }

    $business_start_date = session('business.start_date', config('constants.default_date_format'));

    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25;
@endphp

<script>
    moment.tz.setDefault('{{ Session::get("business.time_zone") }}');
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if(config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });

    var financial_year = {
        start: moment('{{ Session::get("financial_year.start") }}'),
        end: moment('{{ Session::get("financial_year.end") }}'),
    }
    @if(file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    //Default setting for select2
    $.fn.select2.defaults.set("language", "{{session()->get('user.language', config('app.locale'))}}");
    @endif

    var datepicker_date_format = "{{$datepicker_date_format}}";
    var moment_date_format = "{{$moment_date_format}}";
    var moment_time_format = "{{$moment_time_format}}";
    var business_start_date = "{{ $business_start_date }}";

    var app_locale = "{{session()->get('user.language', config('app.locale'))}}";

    var non_utf8_languages = [
        @foreach(config('constants.non_utf8_languages') as $const)
            "{{$const}}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{$default_datatable_page_entries}}";

    var __new_notification_count_interval = "{{config('constants.new_notification_count_interval', 60)}}000";
</script>

@if(file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif

<script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/documents_and_note.js?v=' . $asset_v) }}"></script>

<!-- TODO -->
@if(file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@endif
@php
    $validation_lang_file = 'messages_' . session()->get('user.language', config('app.locale') ) . '.js';
@endphp
@if(file_exists(public_path() . '/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}"></script>
@endif

@if(!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif

<script>

    // add serial model
    $(document).on('click', '.add_serials_row', function () {
        let row_id = $(this).val();
        let product_id = $('#product_id').val();
        let quantity = $('.input_quantity' + row_id).val();
        if (quantity === "" || parseInt(quantity) === 0) {
            swal(
                'Error..!',
                'Please insert quantity to proceed',
                'warning'
            );
        } else {
            let url = "{{ route('serial-create', ['product_id', 'qty', 'row_id']) }}";
            url = url.replace('product_id', product_id);
            url = url.replace('qty', quantity);
            url = url.replace('row_id', row_id);
            $('#serial-create-modal').load(url, function () {
                viewSerialButtonValidation(serial_numbers);
                $('#serial-create-modal').modal('show');
            });
        }

    });

    function serialValidation(serial_number, product_qty) {
        if (serial_number === "" || parseInt(serial_number) === 0) {
            swal(
                'Error..!',
                'Please insert Serial Number',
                'warning'
            );
            return 'Error';
        } else if (parseInt(serial_count) === parseInt(product_qty)) {
            swal(
                'Error..!',
                'Max Quantity Reached..!',
                'warning'
            );
            return 'Error';
        } else {
            return 'Ok';
        }
    }

    function itemExists(serial_numbers, added_serial_number) {
        if (serial_numbers.some(serial => serial === added_serial_number)) {
            swal(
                'Error..!',
                'Serial Number already added..!',
                'warning'
            );
            return 'Exists';
        } else {
            return 'Add';
        }
    }

    // check serial number exists
    function getSerialNumber(product_id, serial_number) {
        let url = "{{ route('get-serial-number', ['product_id', 'serial_number']) }}";
        url = url.replace('product_id', product_id);
        url = url.replace('serial_number', serial_number);
        let serial_status;
        $.ajax({
            method: 'POST',
            url: url,
            data: {
                _token: '{{ csrf_token() }}'
            },
            async: false,
            success: function (response) {
                if (response === 'No') {
                    swal(
                        'Error..!',
                        'Invalid Serial Number or Already sold.!',
                        'warning'
                    );
                }
                serial_status = response;
            }
        });
        return serial_status;
    }

    // check serial number already added to the database
    function checkSerialNumber(product_id, serial_number) {
        let url = "{{ route('check-serial-number', 'serial_number') }}";
        url = url.replace('serial_number', serial_number);
        let serial_status;
        $.ajax({
            method: 'POST',
            url: url,
            data: {
                _token: '{{ csrf_token() }}'
            },
            async: false,
            success: function (response) {
                if (response === 'Have') {
                    swal(
                        'Error..!',
                        'Serial Number Already Added.!',
                        'warning'
                    );
                }
                serial_status = response;
            }
        });
        return serial_status;
    }

    function modelValidation(product_qty, serial_numbers_count) {
        if (parseInt(product_qty) !== serial_numbers_count) {
            $('#serial-modal-done').attr("data-dismiss", "");
            $('#serial-modal-close').attr("data-dismiss", "");
            return 'Not Equal';
        } else {
            $('#serial-modal-done').attr("data-dismiss", "modal");
            $('#serial-modal-close').attr("data-dismiss", "modal");
            return 'Equal';
        }
    }

    function viewSerialButtonValidation(serial_numbers) {
        if (serial_numbers.length >= 1) {
            $('#view-serials').attr('disabled', false);
        } else {
            $('#view-serials').attr('disabled', true);
        }
    }

    function loadTableContentViewSerials(serial_numbers) {
        let html = '';
        serial_numbers.forEach((serial_number, index) => {
            html += '<tr>' +
                '<td>' + serial_number + '</td>' +
                '<td>' +
                '<div class="form-group">' +
                '<button id="' + index + '" style="margin-top: 2px;" type="button" class="btn btn-danger remove-added-serial"><i class="fa fa-trash"></i></button>' +
                '</div>' +
                '</td>' +
                '</tr>';
        });
        $('#t_body').empty().html(html);
    }

    let serial_count = 0;
    let serial_numbers = [];
    // create serials
    $(document).on('click', '.add_serials_create', function () {
        let serial_number = $('#serial_no').val();
        let product_id = $('#product_id').val();
        let product_qty = $('#product_qty').val(); // customer purchasing products item
        let row_id = $('#row_id').val();
        // alert(serial_count);
        let status = serialValidation(serial_number, product_qty);
        // alert(status);
        if (status === 'Ok') {
            let serial_status = checkSerialNumber(product_id, serial_number);
            if (serial_status === 'No') {
                let item_status = itemExists(serial_numbers, serial_number);
                if (item_status === 'Add') {
                    // let serial = {
                    //     product_id : product_id,
                    //     serials : {serial_number}
                    // }
                    serial_numbers.push(serial_number);
                    $('#serial_numbers' + row_id).val(serial_numbers);
                    serial_count++;
                    console.log(serial_numbers);
                    $('#serial_no').val('');
                    $('#length').text(serial_numbers.length);
                    viewSerialButtonValidation(serial_numbers);
                    modelValidation(product_qty, serial_numbers.length);
                }
            }
        }
    });

    // for enter key
    $(document).on('keypress', '#serial_no', function (e) {
        if (e.which === 13) {
            let serial_number = $('#serial_no').val();
            let product_id = $('#product_id').val();
            let product_qty = $('#product_qty').val(); // customer purchasing products item
            let row_id = $('#row_id').val();
            // alert(product_qty);
            // alert(serial_count);
            let status = serialValidation(serial_number, product_qty);
            // alert(status);
            if (status === 'Ok') {
                let serial_status = checkSerialNumber(product_id, serial_number);
                if (serial_status === 'No') {
                    let item_status = itemExists(serial_numbers, serial_number);
                    if (item_status === 'Add') {
                        serial_numbers.push(serial_number);
                        $('#serial_numbers' + row_id).val(serial_numbers);
                        serial_count++;
                        // console.log(serial_numbers);
                        $('#serial_no').val('');
                        $('#length').text(serial_numbers.length);
                        viewSerialButtonValidation(serial_numbers);
                        modelValidation(product_qty, serial_numbers.length);
                    }
                }
            }
        }
    });

    $(document).on('click', '#serial-modal-done, #serial-modal-close', function () {
        let product_qty = $('#product_qty').val();
        let model_validation = modelValidation(product_qty, serial_numbers.length);
        if (model_validation === 'Not Equal') {
            swal(
                'Error..!',
                'Please add serial numbers to match your product qty',
                'warning'
            );
        } else {
            resetSerials();
        }
    });

    function resetSerials() {
        serial_numbers = [];
        serial_count = 0;
    }

    // sell serials create
    $(document).on('click', '.create-sell-serials', function () {
        let row_id = $(this).attr('id');
        let product_id = $('#product_id' + row_id).val();
        let quantity = $('.input_quantity' + row_id).val();
        if (quantity === "" || parseInt(quantity) === 0) {
            swal(
                'Error..!',
                'Please insert quantity to proceed',
                'warning'
            );
        } else {
            let url = "{{ route('serial-sell-create', ['product_id', 'qty', 'row_id']) }}";
            url = url.replace('product_id', product_id);
            url = url.replace('qty', quantity);
            url = url.replace('row_id', row_id);
            $('#serial-sell-create-modal').load(url, function () {
                $('#serial-sell-create-modal').modal('show');
            });
        }
    });

    // add sell serials
    $(document).on('click', '.add_sell_serials_create', function () {
        let serial_number = $('#serial_no_sell').val();
        let product_id = $('#product_id').val();
        let product_qty = $('#product_qty').val();
        let row_id = $('#row_id').val();
        let status = serialValidation(serial_number, product_qty);
        if (status === 'Ok') {
            let serial_status = getSerialNumber(product_id, serial_number);
            if (serial_status === 'Have') {
                let item_status = itemExists(serial_numbers, serial_number);
                if (item_status === 'Add') {
                    serial_numbers.push(serial_number);
                    $('#serial_numbers' + row_id).val(serial_numbers);
                    serial_count++;
                    console.log(serial_numbers);
                    $('#serial_no_sell').val('');
                    $('#length').text(serial_numbers.length);
                    modelValidation(product_qty, serial_numbers.length);
                    viewSerialButtonValidation(serial_numbers);
                }
            }
        }
    });

    // for enter key
    $(document).on('keypress', '#serial_no_sell', function (e) {
        if (e.which === 13) {
            let serial_number = $('#serial_no_sell').val();
            let product_id = $('#product_id').val();
            let product_qty = $('#product_qty').val();
            let row_id = $('#row_id').val();
            let status = serialValidation(serial_number, product_qty);
            if (status === 'Ok') {
                let serial_status = getSerialNumber(product_id, serial_number);
                if (serial_status === 'Have') {
                    let item_status = itemExists(serial_numbers, serial_number);
                    if (item_status === 'Add') {
                        serial_numbers.push(serial_number);
                        $('#serial_numbers' + row_id).val(serial_numbers);
                        serial_count++;
                        console.log(serial_numbers);
                        $('#serial_no_sell').val('');
                        $('#length').text(serial_numbers.length);
                        modelValidation(product_qty, serial_numbers.length);
                        viewSerialButtonValidation(serial_numbers);
                    }
                }
            }
        }
    })

    // for view serial modal
    $(document).on('click', '#view-serials', function () {
        loadTableContentViewSerials(serial_numbers);
        $('#added-serial-number-table').DataTable();
        $('#view-added-serials').modal('show');
    })

    // remove added serial
    $(document).on('click', '.remove-added-serial', function () {
        let index = $(this).attr('id');
        let row_id = $('#row_id').val();
        // alert(index);
        serial_numbers.splice(index, 1);
        loadTableContentViewSerials(serial_numbers);
        serial_count--;
        $('#length').text(serial_numbers.length);
        $('#serial_numbers' + row_id).val(serial_numbers);
    })

</script>

@yield('javascript')

@if(Module::has('Essentials'))
    @includeIf('essentials::layouts.partials.footer_part')
@endif

<script type="text/javascript">
    $(document).ready(function () {
        var locale = "{{session()->get('user.language', config('app.locale'))}}";
        var isRTL = @if(in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl'))) true;
        @else false;
        @endif

        $('#calendar').fullCalendar('option', {
            locale: locale,
            isRTL: isRTL
        });
    });
</script>