@extends('admin.layouts.default')

@section('title', 'Cloudflare Accounts')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="cloud" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Cloudflare Accounts Management
        <div class="pull-right">
            <a href="{{ route('admin.cloudflare.accounts.create') }}" class="btn btn-success">
                <i class="fa fa-plus"></i> Add New Account
            </a>
            <button class="btn btn-info" onclick="syncAllAccounts()">
                <i class="fa fa-refresh"></i> Sync All Accounts
            </button>
        </div>
    </h3>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-users"></i> Cloudflare Accounts ({{ count($accounts) }})
                </h4>
            </div>
            <div class="panel-body">
                @if(count($accounts) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Email</th>
                                    <th>Domains</th>
                                    <th>Status</th>
                                    <th>Last Synced</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts as $account)
                                    <tr>
                                        <td>
                                            <strong>{{ $account->display_name }}</strong>
                                            @if($account->is_default)
                                                <span class="label label-primary">Default</span>
                                            @endif
                                        </td>
                                        <td>{{ $account->email }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $account->domains->count() }}</span>
                                            @if($account->domains->count() > 0)
                                                <div class="small text-muted mt-1">
                                                    @foreach($account->domains->take(3) as $domain)
                                                        <div>{{ $domain->domain_name }}</div>
                                                    @endforeach
                                                    @if($account->domains->count() > 3)
                                                        <div>... and {{ $account->domains->count() - 3 }} more</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($account->is_active)
                                                <span class="label label-success">Active</span>
                                            @else
                                                <span class="label label-default">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($account->last_synced_at)
                                                <span class="small">{{ $account->last_synced_at->diffForHumans() }}</span>
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.cloudflare.accounts.show', $account) }}" 
                                                   class="btn btn-xs btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.cloudflare.accounts.edit', $account) }}" 
                                                   class="btn btn-xs btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button class="btn btn-xs btn-success" 
                                                        onclick="syncAccount({{ $account->id }})" title="Sync">
                                                    <i class="fa fa-refresh"></i>
                                                </button>
                                                <button class="btn btn-xs btn-danger" 
                                                        onclick="deleteAccount({{ $account->id }}, '{{ $account->display_name }}')" 
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
                @else
                    <div class="text-center p-4">
                        <i class="fa fa-cloud fa-3x text-muted mb-3"></i>
                        <h4>No Cloudflare Accounts</h4>
                        <p class="text-muted">Get started by adding your first Cloudflare account.</p>
                        <a href="{{ route('admin.cloudflare.accounts.create') }}" class="btn btn-success">
                            <i class="fa fa-plus"></i> Add First Account
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Domain Modal -->
<div class="modal fade" id="addDomainModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Add Domain to Cloudflare</h4>
            </div>
            <div class="modal-body">
                <form id="addDomainForm">
                    <div class="form-group">
                        <label>Cloudflare Account</label>
                        <select name="account_id" class="form-control" required>
                            <option value="">Select Account</option>
                            @foreach($accounts->where('is_active', true) as $account)
                                <option value="{{ $account->id }}">{{ $account->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Domain Name</label>
                        <input type="text" name="domain_name" class="form-control" placeholder="example.com" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAddDomain()">
                    <i class="fa fa-plus"></i> Add Domain
                </button>
            </div>
        </div>
    </div>
</div>

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
                <p>Are you sure you want to delete this Cloudflare account?</p>
                <div class="alert alert-warning">
                    <strong>Account:</strong> <span id="deleteAccountName"></span>
                </div>
                <p class="text-danger">This will also delete all stored domain and DNS record data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash"></i> Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function syncAccount(accountId) {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch(`{{ route('admin.cloudflare.accounts.sync', '') }}/${accountId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.error);
        }
    })
    .catch(error => {
        showNotification('error', 'Error syncing account');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function syncAllAccounts() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Syncing...';
    button.disabled = true;
    
    fetch('{{ route('admin.cloudflare.accounts.sync-all') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.error);
        }
    })
    .catch(error => {
        showNotification('error', 'Error syncing accounts');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function showAddDomainModal() {
    $('#addDomainModal').modal('show');
}

function submitAddDomain() {
    const form = document.getElementById('addDomainForm');
    const formData = new FormData(form);
    
    fetch('{{ route('admin.cloudflare.accounts.add-domain') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#addDomainModal').modal('hide');
            showNotification('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.error);
        }
    })
    .catch(error => {
        showNotification('error', 'Error adding domain');
    });
}

function deleteAccount(accountId, accountName) {
    document.getElementById('deleteAccountName').textContent = accountName;
    document.getElementById('deleteForm').action = `{{ route('admin.cloudflare.accounts.destroy', '') }}/${accountId}`;
    $('#deleteModal').modal('show');
}

function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const notification = `
        <div class="alert ${alertClass} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fa ${icon}"></i> ${message}
        </div>
    `;
    
    document.querySelector('.page-header').insertAdjacentHTML('afterend', notification);
}
</script>
@endsection