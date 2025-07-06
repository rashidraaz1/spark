@extends('admin.layouts.default')

@section('title', 'Cloudflare Nameservers')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="server" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Cloudflare Nameservers
    </h3>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-server"></i> Get Nameservers
                </h4>
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('admin.cloudflare.nameservers') }}" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Account:</label>
                        <div class="col-sm-4">
                            <select name="account_id" class="form-control" required>
                                <option value="">Select Cloudflare Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ (isset($accountId) && $accountId == $account->id) ? 'selected' : '' }}>
                                        {{ $account->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <label class="col-sm-1 control-label">Domain:</label>
                        <div class="col-sm-3">
                            <input type="text" name="domain" class="form-control" value="{{ $domain ?? '' }}" placeholder="example.com" required>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i> Get Nameservers
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if(isset($error))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <strong>Error:</strong> {{ $error }}
            </div>
        </div>
    </div>
@endif

@if(isset($nameservers))
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-check-circle"></i> Nameservers for {{ $domain }}
                    </h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Domain Status</h5>
                            <p>
                                <span class="label label-{{ $nameservers['status'] == 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($nameservers['status']) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5>Domain</h5>
                            <p><strong>{{ $nameservers['domain'] }}</strong></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Nameservers</h5>
                    <div class="row">
                        @foreach($nameservers['nameservers'] as $ns)
                            <div class="col-md-6">
                                <div class="panel panel-info">
                                    <div class="panel-body">
                                        <i class="fa fa-server"></i> 
                                        <strong>{{ $ns }}</strong>
                                        <button class="btn btn-xs btn-default pull-right" onclick="copyToClipboard('{{ $ns }}')">
                                            <i class="fa fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Actions</h5>
                            <a href="{{ route('admin.cloudflare.dns-records', ['domain' => $domain, 'account_id' => $accountId ?? '']) }}" class="btn btn-success">
                                <i class="fa fa-list"></i> View DNS Records
                            </a>
                            <a href="{{ route('admin.cloudflare.add-record', ['domain' => $domain, 'account_id' => $accountId ?? '']) }}" class="btn btn-warning">
                                <i class="fa fa-plus"></i> Add DNS Record
                            </a>
                            <button class="btn btn-info" onclick="copyAllNameservers()">
                                <i class="fa fa-copy"></i> Copy All Nameservers
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-info-circle"></i> Instructions
                    </h4>
                </div>
                <div class="panel-body">
                    <h5>To use Cloudflare nameservers:</h5>
                    <ol>
                        <li>Log in to your domain registrar's control panel</li>
                        <li>Find the nameserver settings for your domain</li>
                        <li>Replace the existing nameservers with the Cloudflare nameservers shown above</li>
                        <li>Save the changes</li>
                        <li>Wait for DNS propagation (usually 24-48 hours)</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        <strong>Note:</strong> Make sure to copy all nameservers exactly as shown above.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // Show success message
    const originalText = event.target.innerHTML;
    event.target.innerHTML = '<i class="fa fa-check"></i> Copied!';
    setTimeout(() => {
        event.target.innerHTML = originalText;
    }, 2000);
}

function copyAllNameservers() {
    @if(isset($nameservers))
        const nameservers = @json($nameservers['nameservers']);
        const allNameservers = nameservers.join('\n');
        
        const textarea = document.createElement('textarea');
        textarea.value = allNameservers;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        // Show success message
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fa fa-check"></i> All Nameservers Copied!';
        button.className = 'btn btn-success';
        setTimeout(() => {
            button.innerHTML = originalText;
            button.className = 'btn btn-info';
        }, 3000);
    @endif
}
</script>
@endsection