@extends('admin.layouts.default')

@section('title', 'Cloudflare DNS Management')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="cloud" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Cloudflare DNS Management
    </h3>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="livicon" data-name="dashboard" data-size="16" data-loop="true" data-c="#fff" data-hc="white"></i>
                    Dashboard
                </h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-server"></i> Get Nameservers
                                </h4>
                            </div>
                            <div class="panel-body">
                                <p>Check Cloudflare nameservers for your domain</p>
                                <a href="{{ route('admin.cloudflare.nameservers') }}" class="btn btn-info btn-block">
                                    <i class="fa fa-search"></i> Check Nameservers
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-list"></i> DNS Records
                                </h4>
                            </div>
                            <div class="panel-body">
                                <p>View and manage DNS records for your domains</p>
                                <a href="{{ route('admin.cloudflare.dns-records') }}" class="btn btn-success btn-block">
                                    <i class="fa fa-eye"></i> View DNS Records
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-plus"></i> Add DNS Record
                                </h4>
                            </div>
                            <div class="panel-body">
                                <p>Add new DNS records to your domains</p>
                                <a href="{{ route('admin.cloudflare.add-record') }}" class="btn btn-warning btn-block">
                                    <i class="fa fa-plus"></i> Add DNS Record
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-users"></i> Accounts
                                </h4>
                            </div>
                            <div class="panel-body">
                                <p>Manage your Cloudflare accounts</p>
                                <a href="{{ route('admin.cloudflare.accounts.index') }}" class="btn btn-primary btn-block">
                                    <i class="fa fa-cog"></i> Manage Accounts
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-question-circle"></i> Help
                                </h4>
                            </div>
                            <div class="panel-body">
                                <p>Need help with Cloudflare DNS management?</p>
                                <button class="btn btn-default btn-block" onclick="showHelp()">
                                    <i class="fa fa-question-circle"></i> View Help
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-cog"></i> Quick Actions
                                </h4>
                            </div>
                            <div class="panel-body">
                                <form class="form-horizontal" id="quickActionForm">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Account:</label>
                                        <div class="col-sm-3">
                                            <select class="form-control" id="quickAccount">
                                                <option value="">Select Account</option>
                                                @foreach($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->display_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label class="col-sm-1 control-label">Domain:</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="quickDomain" placeholder="example.com">
                                        </div>
                                        <div class="col-sm-3">
                                            <button type="button" class="btn btn-info btn-sm" onclick="quickGetNameservers()">
                                                <i class="fa fa-server"></i> Nameservers
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" onclick="quickGetRecords()">
                                                <i class="fa fa-list"></i> DNS Records
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                
                                <div id="quickResults" class="mt-3" style="display: none;">
                                    <div class="panel panel-info">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">Results</h4>
                                        </div>
                                        <div class="panel-body">
                                            <pre id="quickResultsContent"></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Cloudflare DNS Management Help</h4>
            </div>
            <div class="modal-body">
                <h5>Getting Started</h5>
                <p>To use Cloudflare DNS management, you need to configure your Cloudflare API credentials in your environment file:</p>
                <ul>
                    <li><strong>CLOUDFLARE_EMAIL</strong> - Your Cloudflare account email</li>
                    <li><strong>CLOUDFLARE_API_KEY</strong> - Your Cloudflare Global API Key</li>
                </ul>
                
                <h5>Features</h5>
                <ul>
                    <li><strong>Nameservers:</strong> Check Cloudflare nameservers for any domain</li>
                    <li><strong>DNS Records:</strong> View all DNS records for a domain</li>
                    <li><strong>Add Records:</strong> Add new DNS records (A, AAAA, CNAME, MX, TXT, etc.)</li>
                    <li><strong>Edit Records:</strong> Update existing DNS records</li>
                    <li><strong>Delete Records:</strong> Remove DNS records</li>
                </ul>
                
                <h5>Supported Record Types</h5>
                <ul>
                    <li><strong>A:</strong> IPv4 address</li>
                    <li><strong>AAAA:</strong> IPv6 address</li>
                    <li><strong>CNAME:</strong> Canonical name</li>
                    <li><strong>MX:</strong> Mail exchange (requires priority)</li>
                    <li><strong>TXT:</strong> Text record</li>
                    <li><strong>NS:</strong> Name server</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showHelp() {
    $('#helpModal').modal('show');
}

function quickGetNameservers() {
    const domain = document.getElementById('quickDomain').value;
    const accountId = document.getElementById('quickAccount').value;
    
    if (!domain || !accountId) {
        alert('Please select an account and enter a domain');
        return;
    }
    
    fetch(`{{ route('admin.cloudflare.api.nameservers') }}?domain=${encodeURIComponent(domain)}&account_id=${encodeURIComponent(accountId)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('quickResults').style.display = 'block';
            document.getElementById('quickResultsContent').textContent = JSON.stringify(data, null, 2);
        })
        .catch(error => {
            document.getElementById('quickResults').style.display = 'block';
            document.getElementById('quickResultsContent').textContent = 'Error: ' + error.message;
        });
}

function quickGetRecords() {
    const domain = document.getElementById('quickDomain').value;
    const accountId = document.getElementById('quickAccount').value;
    
    if (!domain || !accountId) {
        alert('Please select an account and enter a domain');
        return;
    }
    
    fetch(`{{ route('admin.cloudflare.api.dns-records') }}?domain=${encodeURIComponent(domain)}&account_id=${encodeURIComponent(accountId)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('quickResults').style.display = 'block';
            document.getElementById('quickResultsContent').textContent = JSON.stringify(data, null, 2);
        })
        .catch(error => {
            document.getElementById('quickResults').style.display = 'block';
            document.getElementById('quickResultsContent').textContent = 'Error: ' + error.message;
        });
}
</script>
@endsection