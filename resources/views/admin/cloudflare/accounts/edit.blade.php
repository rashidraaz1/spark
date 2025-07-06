@extends('admin.layouts.default')

@section('title', 'Edit Cloudflare Account')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="edit" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Edit Cloudflare Account: {{ $account->display_name }}
    </h3>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-edit"></i> Account Information
                </h4>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.cloudflare.accounts.update', $account) }}" class="form-horizontal">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Name <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" placeholder="My Cloudflare Account" required>
                            <span class="help-block">A friendly name to identify this account</span>
                            @if($errors->has('name'))
                                <span class="help-block text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Cloudflare Email <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="email" name="email" class="form-control" value="{{ old('email', $account->email) }}" placeholder="your@email.com" required>
                            <span class="help-block">The email address associated with your Cloudflare account</span>
                            @if($errors->has('email'))
                                <span class="help-block text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Global API Key</label>
                        <div class="col-sm-9">
                            <input type="password" name="api_key" class="form-control" placeholder="Leave blank to keep current API key">
                            <span class="help-block">
                                <strong>Current:</strong> ••••••••••••••••••••••••••••••••••••••••
                                <br>Enter new API key only if you want to update it
                            </span>
                            @if($errors->has('api_key'))
                                <span class="help-block text-danger">{{ $errors->first('api_key') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">API Token (Optional)</label>
                        <div class="col-sm-9">
                            <input type="password" name="api_token" class="form-control" placeholder="Leave blank to keep current API token">
                            <span class="help-block">
                                @if($account->api_token)
                                    <strong>Current:</strong> ••••••••••••••••••••••••••••••••••••••••
                                    <br>Enter new API token only if you want to update it
                                @else
                                    Optional: Use API Token for enhanced security (recommended for production)
                                @endif
                            </span>
                            @if($errors->has('api_token'))
                                <span class="help-block text-danger">{{ $errors->first('api_token') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                                    Account is active
                                </label>
                                <span class="help-block">Inactive accounts will be excluded from operations</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $account->is_default) ? 'checked' : '' }}>
                                    Set as default account
                                </label>
                                <span class="help-block">The default account will be pre-selected in forms</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this account">{{ old('notes', $account->notes) }}</textarea>
                            @if($errors->has('notes'))
                                <span class="help-block text-danger">{{ $errors->first('notes') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Update Account
                            </button>
                            <a href="{{ route('admin.cloudflare.accounts.index') }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Accounts
                            </a>
                            <button type="button" class="btn btn-info" onclick="testConnection()">
                                <i class="fa fa-test"></i> Test Connection
                            </button>
                            <button type="button" class="btn btn-warning" onclick="syncAccount()">
                                <i class="fa fa-refresh"></i> Sync Account
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-info-circle"></i> Account Details
                </h4>
            </div>
            <div class="panel-body">
                <dl class="dl-horizontal">
                    <dt>Account ID:</dt>
                    <dd>{{ $account->id }}</dd>
                    
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
                    
                    <dt>Domains:</dt>
                    <dd>{{ $account->domains->count() }}</dd>
                    
                    <dt>Total Records:</dt>
                    <dd>{{ $account->domains->sum('dns_records_count') }}</dd>
                    
                    <dt>Created:</dt>
                    <dd>{{ $account->created_at->format('M j, Y g:i A') }}</dd>
                    
                    <dt>Last Synced:</dt>
                    <dd>
                        @if($account->last_synced_at)
                            {{ $account->last_synced_at->diffForHumans() }}
                        @else
                            <span class="text-muted">Never</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
        
        @if($account->domains->count() > 0)
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-globe"></i> Domains ({{ $account->domains->count() }})
                    </h4>
                </div>
                <div class="panel-body">
                    @foreach($account->domains->take(10) as $domain)
                        <div class="domain-item mb-2">
                            <strong>{{ $domain->domain_name }}</strong>
                            <span class="label label-{{ $domain->status_badge_class }}">{{ ucfirst($domain->status) }}</span>
                            <div class="small text-muted">
                                {{ $domain->dns_records_count }} DNS records
                            </div>
                        </div>
                        @if(!$loop->last) <hr class="my-1"> @endif
                    @endforeach
                    
                    @if($account->domains->count() > 10)
                        <div class="text-center mt-2">
                            <small class="text-muted">... and {{ $account->domains->count() - 10 }} more domains</small>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-warning"></i> Security
                </h4>
            </div>
            <div class="panel-body">
                <p><strong>API Key Protection:</strong> Your API keys are encrypted using Laravel's built-in encryption before being stored in the database.</p>
                
                <p><strong>Updating Credentials:</strong> Leave the API key fields blank if you don't want to change them.</p>
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Test the connection after updating credentials to ensure they work correctly.
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('error'))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        </div>
    </div>
@endif

<script>
function testConnection() {
    const email = document.querySelector('input[name="email"]').value;
    const apiKey = document.querySelector('input[name="api_key"]').value;
    
    if (!email) {
        alert('Please enter email before testing connection.');
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;
    
    // If no new API key provided, test existing account connection
    if (!apiKey) {
        fetch('{{ route('admin.cloudflare.accounts.test-connection', $account) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', `✅ ${data.message}`);
            } else {
                showNotification('error', `❌ Connection failed: ${data.error}`);
            }
        })
        .catch(error => {
            showNotification('error', '❌ Connection test failed. Please check your credentials.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    } else {
        // Test with new credentials
        const formData = new FormData();
        formData.append('email', email);
        formData.append('api_key', apiKey);
        
        fetch('{{ route('admin.cloudflare.accounts.test-connection.new') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', `✅ ${data.message}`);
            } else {
                showNotification('error', `❌ Connection failed: ${data.error}`);
            }
        })
        .catch(error => {
            showNotification('error', '❌ Connection test failed. Please check your credentials.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

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

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating Account...';
        submitButton.disabled = true;
    });
});
</script>

<style>
.domain-item {
    padding: 8px 0;
}

.my-1 {
    margin: 0.25rem 0;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

.mt-2 {
    margin-top: 0.5rem;
}
</style>
@endsection