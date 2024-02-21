@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Audit Log View
            {{-- <small>Manage Serials</small> --}}
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
                {{--                    <div class="box-header with-border">--}}
                {{--                        <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>--}}
                {{--                    </div>--}}
                <!-- /.box-header -->
                    <div class="box-body">
                        <table id="audit_log_table" class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td>Description</td>
                                    <td>
                                        @if ($audit_log->description == 'created')
                                            <span class="label label-success">Created</span>
                                        @elseif ($audit_log->description == 'updated')
                                            <span class="label label-primary">Updated</span>
                                        @else
                                            <span class="label label-danger">Deleted</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Invoice No / Ref No</td>
                                    <td>
                                        @if (!empty($audit_log->transaction->invoice_no))
                                            {{ '#'.$audit_log->transaction->invoice_no }}
                                        @else
                                            {{ '#'.$audit_log->transaction->ref_no }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Subject Type</td>
                                    <td>{{ $audit_log->subject_type }}</td>
                                </tr>
                                <tr>
                                    <td>User</td>
                                    <td>{{ $audit_log->user->username }}</td>
                                </tr>
                                <tr>
                                    <td>Host</td>
                                    <td>{{ $audit_log->host }}</td>
                                </tr>
                                <tr>
                                    <td>Created At</td>
                                    <td>{{ $audit_log->created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
        </div>
    </section>

@endsection