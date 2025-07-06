@extends('admin.layouts.default')

@section('title', 'Cloudflare Account Details')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="eye" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Cloudflare Account: {{ $account->display_name }}
        <div class="pull-right">
            <a href="{{ route('admin.cloudflare.accounts.edit', $account) }}" class="btn btn-warning">
                <i class="fa fa-edit"></i> Edit Account
            </a>
            <a href="{{ route('admin.cloudflare.accounts.index') }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back to Accounts
            </a>
        </div>
    </h3>
</div>

<div class="row">
    <!-- Account Information -->
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-user"></i> Account Information
                </h4>
            </div>
            <div class="panel-body">
                <dl class="dl-horizontal">
                    <dt>Account Name:</dt>
                    <dd><strong>{{ $account->name ?: 'N/A' }}</strong></dd>
                    
                    <dt>Email:</dt>
                    <dd>{{ $account->email }}</dd>
                    
                    <dt>Status:</dt>
                    <dd>
                        @if($account->is_active)
                            <span class="label label-success">Active</span>
                        @else
                            <span class="label label-default">Inactive</span>
                        @endif
                        
                        @if($account->is_default)
                            <span class="label label-primary">Default</span>
                        @endif
                    </dd>
                    
                    <dt>Account ID:</dt>
                    <dd><code>{{ $account->id }}</code></dd>
                    
                    <dt>Created:</dt>
                    <dd>{{ $account->created_at->format('M j, Y g:i A') }}</dd>
                    
                    <dt>Last Updated:</dt>
                    <dd>{{ $account->updated_at->format('M j, Y g:i A') }}</dd>
                    
                    <dt>Last Synced:</dt>
                    <dd>
                        @if($account->last_synced_at)
                            {{ $account->last_synced_at->diffForHumans() }}
                            <br><small class="text-muted">{{ $account->last_synced_at->format('M j, Y g:i A') }}</small>
                        @else
                            <span class="text-muted">Never synced</span>
                        @endif
                    </dd>
                </dl>
                
                @if($account->notes)
                    <hr>
                    <h5>Notes:</h5>
                    <p class="text-muted">{{ $account->notes }}</p>
                @endif
            </div>
        </div>
        
        <!-- Account Actions -->
        <div class="panel panel-info">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-cogs"></i> Actions
                </h4>
            </div>
            <div class="panel-body">
                <button class="btn btn-success btn-block" onclick="syncAccount()">
                    <i class="fa fa-refresh"></i> Sync Account Now
                </button>
                
                <button class="btn btn-info btn-block" onclick="testConnection()">
                    <i class="fa fa-test"></i> Test API Connection
                </button>
                
                <button class="btn btn-warning btn-block" onclick="showAddDomainModal()">
                    <i class="fa fa-plus"></i> Add New Domain
                </button>
                
                <hr>
                
                <a href="{{ route('admin.cloudflare.nameservers') }}?account_id={{ $account->id }}" class="btn btn-default btn-block">
                    <i class="fa fa-server"></i> Get Nameservers
                </a>
                
                <a href="{{ route('admin.cloudflare.dns-records') }}?account_id={{ $account->id }}" class="btn btn-default btn-block">
                    <i class="fa fa-list"></i> Manage DNS Records
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-body text-center">
                        <h2 class="text-success">{{ $account->domains->count() }}</h2>
                        <p class="text-muted">Total Domains</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-body text-center">
                        <h2 class="text-info">{{ $account->domains->where('status', 'active')->count() }}</h2>
                        <p class="text-muted">Active Domains</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-warning">
                    <div class="panel-body text-center">
                        <h2 class="text-warning">{{ $account->domains->sum('dns_records_count') }}</h2>
                        <p class="text-muted">DNS Records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-body text-center">
                        <h2 class="text-primary">{{ $account->domains->where('sync_status', 'synced')->count() }}</h2>
                        <p class="text-muted">Synced Domains</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Domains List -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-globe"></i> Domains ({{ $account->domains->count() }})
                    <div class="pull-right">
                        <button class="btn btn-xs btn-info" onclick="refreshDomainsList()">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                </h4>
            </div>
            <div class="panel-body">
                @if($account->domains->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>DNS Records</th>
                                    <th>Plan</th>
                                    <th>Last Synced</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($account->domains as $domain)
                                    <tr>
                                        <td>
                                            <strong>{{ $domain->domain_name }}</strong>
                                            @if($domain->nameservers)
                                                <br><small class="text-muted">{{ count($domain->nameservers) }} nameservers</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="label label-{{ $domain->status_badge_class }}">
                                                {{ ucfirst($domain->status) }}
                                            </span>
                                            <br>
                                            <span class="label label-{{ $domain->sync_status_badge_class }}">
                                                {{ ucfirst($domain->sync_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $domain->dns_records_count }}</span>
                                            @if($domain->dnsRecords->count() > 0)
                                                <br><small class="text-muted">
                                                    @php
                                                        $recordTypes = $domain->dnsRecords->groupBy('type')->map->count();
                                                    @endphp
                                                    @foreach($recordTypes->take(3) as $type => $count)
                                                        {{ $type }}:{{ $count }}{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $domain->plan_name ?? 'Free' }}
                                        </td>
                                        <td>
                                            @if($domain->last_synced_at)
                                                <span class="small">{{ $domain->last_synced_at->diffForHumans() }}</span>
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.cloudflare.nameservers') }}?account_id={{ $account->id }}&domain={{ $domain->domain_name }}" 
                                                   class="btn btn-xs btn-info" title="Nameservers">
                                                    <i class="fa fa-server"></i>
                                                </a>
                                                <a href="{{ route('admin.cloudflare.dns-records') }}?account_id={{ $account->id }}&domain={{ $domain->domain_name }}" 
                                                   class="btn btn-xs btn-success" title="DNS Records">
                                                    <i class="fa fa-list"></i>
                                                </a>
                                                <a href="{{ route('admin.cloudflare.add-record') }}?account_id={{ $account->id }}&domain={{ $domain->domain_name }}" 
                                                   class="btn btn-xs btn-warning" title="Add Record">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center p-4">
                        <i class="fa fa-globe fa-3x text-muted mb-3"></i>
                        <h4>No Domains Found</h4>
                        <p class="text-muted">This account doesn't have any domains yet, or they haven't been synced.</p>
                        <button class="btn btn-success" onclick="syncAccount()">
                            <i class="fa fa-refresh"></i> Sync Account
                        </button>
                        <button class="btn btn-warning" onclick="showAddDomainModal()">
                            <i class="fa fa-plus"></i> Add Domain
                        </button>
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
                <h4 class="modal-title">Add Domain to {{ $account->display_name }}</h4>
            </div>
            <div class="modal-body">
                <form id="addDomainForm">
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                    <div class="form-group">
                        <label>Domain Name</label>
                        <input type="text" name="domain_name" class="form-control" placeholder="example.com" required>
                        <span class="help-block">Enter the domain name you want to add to this Cloudflare account</span>
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

<script>
function syncAccount() {
    if (!confirm('Sync this account now? This will update all domains and DNS records.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Syncing...';
    button.disabled = true;
    
    fetch('{{ route('admin.cloudflare.accounts.sync', $account) }}', {
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

function testConnection() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;
    
    // Test connection using the account's credentials
    fetch('https://api.cloudflare.com/client/v4/zones', {
        headers: {
            'X-Auth-Email': '{{ $account->email }}',
            'X-Auth-Key': '{{ $account->api_key }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', `✅ Connection successful! Found ${data.result.length} domains.`);
        } else {
            const error = data.errors && data.errors.length > 0 ? data.errors[0].message : 'Invalid credentials';
            showNotification('error', `❌ Connection failed: ${error}`);
        }
    })
    .catch(error => {
        showNotification('error', '❌ Connection test failed. Please check account credentials.');
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
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;
    
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
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function refreshDomainsList() {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    // Just reload the page for now
    setTimeout(() => {
        location.reload();
    }, 500);
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
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert-dismissible');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>

<style>
.mb-3 {
    margin-bottom: 1rem;
}

.p-4 {
    padding: 1.5rem;
}

.domain-status {
    font-size: 0.85em;
}
</style>
@endsection