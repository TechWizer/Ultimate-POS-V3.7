@extends('layouts.app')
@section('title', 'Serials')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Serials
            <small>Manage Serials</small>
        </h1>
        <!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product</label>
                                <select id="product_id" name="product_id" class="form-control select2"
                                        style="width: 100%;">
                                    <option value="">Select</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- /.form-group -->
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Serial Number</label>
                                <select id="serial_number_id" name="serial_number_id" class="form-control select2"
                                        style="width: 100%;">
                                    <option value="">Select</option>
                                    @foreach($serial_numbers as $serial_number)
                                        <option value="{{ $serial_number->id }}">{{ $serial_number->serial_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- /.form-group -->
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select id="status" name="status" class="form-control select2" style="width: 100%;">
                                    <option value="">Select</option>
                                    <option value="available">Available</option>
                                    <option value="sold">Sold</option>
                                    <option value="returned">Returned</option>
                                </select>
                            </div>
                            <!-- /.form-group -->
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                {{--                    <div class="box-header with-border">--}}
                {{--                        <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>--}}
                {{--                    </div>--}}
                <!-- /.box-header -->
                    <div class="box-body">
                        <table id="serials_table" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>Purchase Ref No / Invoice No</th>
                                {{--                                <th>Invoice No</th>--}}
                            </tr>
                            </thead>
                            <tbody>
                            {{--                            <tr>--}}
                            {{--                                <td>Trident</td>--}}
                            {{--                                <td>Internet--}}
                            {{--                                    Explorer 4.0--}}
                            {{--                                </td>--}}
                            {{--                                <td>Win 95+</td>--}}
                            {{--                                <td> 4</td>--}}
                            {{--                                <td>X</td>--}}
                            {{--                            </tr>--}}
                            </tbody>
                            <tfoot>
                            <tr>
                                <th>Product Name</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>Purchase Ref No / Invoice No</th>
                                {{--                                <th>Invoice No</th>--}}
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
        </div>
    </section>

@endsection

@section('javascript')

    @include('serial.scripts')

@endsection