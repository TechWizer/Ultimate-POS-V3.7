<?php

namespace App\Http\Controllers;

use App\AuditLog;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $auditlogs = AuditLog::with(['user'])->orderBy('id', 'desc')
            ->select(
                'id',
                'description',
                'subject_id',
                'subject_type',
                'user_id',
                'properties',
                'host',
                'created_at'
                )
            ->get();
            
            return DataTables::of($auditlogs)
            ->addColumn('action', function($row){
                $html =
                    '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu">';

                if (auth()->user()->can('product.view')) {
                    $html .=
                        '<li><a href="' . action('AuditLogController@show', [encrypt($row->id)]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })
            ->editColumn('description', function($row){
                if ($row->description == 'created') {
                    $html = '<span class="label label-success">Created</span>';
                } elseif ($row->description == 'updated') {
                    $html = '<span class="label label-primary">Updated</span>';
                } elseif ($row->description == 'deleted') {
                    $html = '<span class="label label-danger">Deleted</span>';
                }
                return $html;
            })
            ->editColumn('user', function($row){
                return $row->user->username;
            })
            ->rawColumns(['action', 'description'])
            ->make(true);
        }
        return view('audit_log.index');
    }

    public function show($id)
    {
        $decrypt_id = decrypt($id);
        $audit_log = AuditLog::with(['user', 'transaction'])->find($decrypt_id);
        return view('audit_log.show', compact('audit_log'));
    }

}
