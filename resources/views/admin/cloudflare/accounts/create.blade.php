@extends('admin.layouts.default')

@section('title', 'Add Cloudflare Account')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="plus" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Add New Cloudflare Account
    </h3>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-plus"></i> Account Information
                </h4>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.cloudflare.accounts.store') }}" class="form-horizontal">
                    @csrf
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Name <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="My Cloudflare Account" required>
                            <span class="help-block">A friendly name to identify this account</span>
                            @if($errors->has('name'))
                                <span class="help-block text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Cloudflare Email <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="your@email.com" required>
                            <span class="help-block">The email address associated with your Cloudflare account</span>
                            @if($errors->has('email'))
                                <span class="help-block text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Global API Key <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="password" name="api_key" class="form-control" value="{{ old('api_key') }}" placeholder="Enter your Cloudflare Global API Key" required>
                            <span class="help-block">
                                Get this from: <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">Cloudflare Dashboard → My Profile → API Tokens</a>
                            </span>
                            @if($errors->has('api_key'))
                                <span class="help-block text-danger">{{ $errors->first('api_key') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">API Token (Optional)</label>
                        <div class="col-sm-9">
                            <input type="password" name="api_token" class="form-control" value="{{ old('api_token') }}" placeholder="Enter API Token (if using instead of Global API Key)">
                            <span class="help-block">Optional: Use API Token for enhanced security (recommended for production)</span>
                            @if($errors->has('api_token'))
                                <span class="help-block text-danger">{{ $errors->first('api_token') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                                    Set as default account
                                </label>
                                <span class="help-block">The default account will be pre-selected in forms</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this account">{{ old('notes') }}</textarea>
                            @if($errors->has('notes'))
                                <span class="help-block text-danger">{{ $errors->first('notes') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-plus"></i> Add Account & Sync Domains
                            </button>
                            <a href="{{ route('admin.cloudflare.accounts.index') }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Accounts
                            </a>
                            <button type="button" class="btn btn-info" onclick="testConnection()">
                                <i class="fa fa-test"></i> Test Connection
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
                    <i class="fa fa-info-circle"></i> How to Get API Credentials
                </h4>
            </div>
            <div class="panel-body">
                <h5>Step 1: Login to Cloudflare</h5>
                <p>Go to <a href="https://dash.cloudflare.com" target="_blank">dash.cloudflare.com</a> and login to your account.</p>
                
                <h5>Step 2: Navigate to API Tokens</h5>
                <p>Click on your profile icon → <strong>My Profile</strong> → <strong>API Tokens</strong></p>
                
                <h5>Step 3: Get Global API Key</h5>
                <p>Find "Global API Key" section and click <strong>View</strong>. You'll need to enter your password.</p>
                
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i>
                    <strong>Security Note:</strong> API keys are encrypted and stored securely in the database.
                </div>
                
                <h5>Alternative: API Token (Recommended)</h5>
                <p>For better security, you can create a custom API token instead of using the Global API Key.</p>
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>What happens next?</strong> After adding the account, we'll automatically sync all your domains and DNS records.
                </div>
            </div>
        </div>
        
        <div class="panel panel-success">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-check-circle"></i> Features
                </h4>
            </div>
            <div class="panel-body">
                <ul class="list-unstyled">
                    <li><i class="fa fa-check text-success"></i> Automatic domain synchronization</li>
                    <li><i class="fa fa-check text-success"></i> DNS records management</li>
                    <li><i class="fa fa-check text-success"></i> Nameserver information</li>
                    <li><i class="fa fa-check text-success"></i> Real-time updates</li>
                    <li><i class="fa fa-check text-success"></i> Secure API key storage</li>
                    <li><i class="fa fa-check text-success"></i> Multi-account support</li>
                </ul>
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
    
    if (!email || !apiKey) {
        alert('Please enter both email and API key before testing connection.');
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;
    
    // Use server-side test endpoint to avoid CORS issues
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

// Show/hide API Token field based on selection
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding Account...';
        submitButton.disabled = true;
    });
});
</script>
@endsection