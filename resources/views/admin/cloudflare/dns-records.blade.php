@extends('admin.layouts.default')

@section('title', 'DNS Records')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="list" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        DNS Records Management
    </h3>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-search"></i> Search DNS Records
                </h4>
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('admin.cloudflare.dns-records') }}" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Domain:</label>
                        <div class="col-sm-4">
                            <input type="text" name="domain" class="form-control" value="{{ $domain ?? '' }}" placeholder="example.com" required>
                        </div>
                        <label class="col-sm-2 control-label">Record Type:</label>
                        <div class="col-sm-2">
                            <select name="type" class="form-control">
                                <option value="">All Types</option>
                                <option value="A" {{ ($type ?? '') == 'A' ? 'selected' : '' }}>A</option>
                                <option value="AAAA" {{ ($type ?? '') == 'AAAA' ? 'selected' : '' }}>AAAA</option>
                                <option value="CNAME" {{ ($type ?? '') == 'CNAME' ? 'selected' : '' }}>CNAME</option>
                                <option value="MX" {{ ($type ?? '') == 'MX' ? 'selected' : '' }}>MX</option>
                                <option value="TXT" {{ ($type ?? '') == 'TXT' ? 'selected' : '' }}>TXT</option>
                                <option value="NS" {{ ($type ?? '') == 'NS' ? 'selected' : '' }}>NS</option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> {{ session('success') }}
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        </div>
    </div>
@endif

@if(isset($error))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <strong>Error:</strong> {{ $error }}
            </div>
        </div>
    </div>
@endif

@if(isset($records))
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-list"></i> DNS Records for {{ $domain }}
                        <div class="pull-right">
                            <a href="{{ route('admin.cloudflare.add-record', ['domain' => $domain]) }}" class="btn btn-sm btn-warning">
                                <i class="fa fa-plus"></i> Add Record
                            </a>
                        </div>
                    </h4>
                </div>
                <div class="panel-body">
                    @if(count($records['records']) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>Content</th>
                                        <th>TTL</th>
                                        <th>Priority</th>
                                        <th>Proxied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records['records'] as $record)
                                        <tr>
                                            <td>
                                                <span class="label label-{{ $record['type'] == 'A' ? 'primary' : ($record['type'] == 'CNAME' ? 'info' : 'default') }}">
                                                    {{ $record['type'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ $record['name'] }}</strong>
                                            </td>
                                            <td>
                                                <code>{{ $record['content'] }}</code>
                                            </td>
                                            <td>
                                                {{ $record['ttl'] == 1 ? 'Auto' : $record['ttl'] . 's' }}
                                            </td>
                                            <td>
                                                {{ $record['priority'] ?? '-' }}
                                            </td>
                                            <td>
                                                @if($record['proxied'] ?? false)
                                                    <span class="label label-success">Yes</span>
                                                @else
                                                    <span class="label label-default">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.cloudflare.edit-record', ['domain' => $domain, 'record_id' => $record['id']]) }}" 
                                                       class="btn btn-xs btn-info" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-xs btn-danger" 
                                                            onclick="deleteRecord('{{ $record['id'] }}', '{{ $record['name'] }}', '{{ $record['type'] }}')"
                                                            title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    <strong>{{ count($records['records']) }}</strong> DNS records found for {{ $domain }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fa fa-warning"></i> No DNS records found for {{ $domain }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this DNS record?</p>
                <div class="alert alert-warning">
                    <strong>Type:</strong> <span id="deleteRecordType"></span><br>
                    <strong>Name:</strong> <span id="deleteRecordName"></span>
                </div>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fa fa-trash"></i> Delete Record
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteRecordId = null;

function deleteRecord(recordId, recordName, recordType) {
    deleteRecordId = recordId;
    document.getElementById('deleteRecordType').textContent = recordType;
    document.getElementById('deleteRecordName').textContent = recordName;
    $('#deleteModal').modal('show');
}

function confirmDelete() {
    if (!deleteRecordId) return;
    
    const formData = new FormData();
    formData.append('domain', '{{ $domain ?? '' }}');
    formData.append('record_id', deleteRecordId);
    
    fetch('{{ route('admin.cloudflare.delete-record') }}', {
        method: 'DELETE',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#deleteModal').modal('hide');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>
@endsection