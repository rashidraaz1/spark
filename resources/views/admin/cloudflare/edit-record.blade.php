@extends('admin.layouts.default')

@section('title', 'Edit DNS Record')

@section('content')
<div class="page-header">
    <h3>
        <i class="livicon" data-name="edit" data-c="#28a745" data-hc="#28a745" data-size="18" data-loop="true"></i>
        Edit DNS Record
    </h3>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-edit"></i> Edit DNS Record
                </h4>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.cloudflare.edit-record.update') }}" class="form-horizontal">
                    @csrf
                    <input type="hidden" name="record_id" value="{{ $record['id'] }}">
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Domain <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="domain" class="form-control" value="{{ old('domain', $domain) }}" placeholder="example.com" required>
                            @if($errors->has('domain'))
                                <span class="help-block text-danger">{{ $errors->first('domain') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Record Type <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="type" class="form-control" id="recordType" required onchange="updateFormFields()">
                                <option value="">Select Record Type</option>
                                <option value="A" {{ old('type', $record['type']) == 'A' ? 'selected' : '' }}>A - IPv4 Address</option>
                                <option value="AAAA" {{ old('type', $record['type']) == 'AAAA' ? 'selected' : '' }}>AAAA - IPv6 Address</option>
                                <option value="CNAME" {{ old('type', $record['type']) == 'CNAME' ? 'selected' : '' }}>CNAME - Canonical Name</option>
                                <option value="MX" {{ old('type', $record['type']) == 'MX' ? 'selected' : '' }}>MX - Mail Exchange</option>
                                <option value="TXT" {{ old('type', $record['type']) == 'TXT' ? 'selected' : '' }}>TXT - Text</option>
                                <option value="NS" {{ old('type', $record['type']) == 'NS' ? 'selected' : '' }}>NS - Name Server</option>
                            </select>
                            @if($errors->has('type'))
                                <span class="help-block text-danger">{{ $errors->first('type') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="name" class="form-control" value="{{ old('name', $record['name']) }}" placeholder="subdomain or @ for root" required>
                            <span class="help-block">Use @ for root domain, or enter subdomain name (e.g., www, mail, etc.)</span>
                            @if($errors->has('name'))
                                <span class="help-block text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Content <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="content" class="form-control" value="{{ old('content', $record['content']) }}" placeholder="" required id="contentField">
                            <span class="help-block" id="contentHelp">Enter the target value for this record</span>
                            @if($errors->has('content'))
                                <span class="help-block text-danger">{{ $errors->first('content') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">TTL (seconds)</label>
                        <div class="col-sm-9">
                            <select name="ttl" class="form-control">
                                <option value="1" {{ old('ttl', $record['ttl']) == '1' ? 'selected' : '' }}>Auto</option>
                                <option value="120" {{ old('ttl', $record['ttl']) == '120' ? 'selected' : '' }}>2 minutes</option>
                                <option value="300" {{ old('ttl', $record['ttl']) == '300' ? 'selected' : '' }}>5 minutes</option>
                                <option value="600" {{ old('ttl', $record['ttl']) == '600' ? 'selected' : '' }}>10 minutes</option>
                                <option value="900" {{ old('ttl', $record['ttl']) == '900' ? 'selected' : '' }}>15 minutes</option>
                                <option value="1800" {{ old('ttl', $record['ttl']) == '1800' ? 'selected' : '' }}>30 minutes</option>
                                <option value="3600" {{ old('ttl', $record['ttl']) == '3600' ? 'selected' : '' }}>1 hour</option>
                                <option value="7200" {{ old('ttl', $record['ttl']) == '7200' ? 'selected' : '' }}>2 hours</option>
                                <option value="18000" {{ old('ttl', $record['ttl']) == '18000' ? 'selected' : '' }}>5 hours</option>
                                <option value="43200" {{ old('ttl', $record['ttl']) == '43200' ? 'selected' : '' }}>12 hours</option>
                                <option value="86400" {{ old('ttl', $record['ttl']) == '86400' ? 'selected' : '' }}>1 day</option>
                            </select>
                            @if($errors->has('ttl'))
                                <span class="help-block text-danger">{{ $errors->first('ttl') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group" id="priorityField" style="display: {{ ($record['type'] == 'MX') ? 'block' : 'none' }};">
                        <label class="col-sm-3 control-label">Priority</label>
                        <div class="col-sm-9">
                            <input type="number" name="priority" class="form-control" value="{{ old('priority', $record['priority'] ?? '') }}" placeholder="10" min="0" max="65535">
                            <span class="help-block">Priority for MX records (lower values have higher priority)</span>
                            @if($errors->has('priority'))
                                <span class="help-block text-danger">{{ $errors->first('priority') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Update DNS Record
                            </button>
                            <a href="{{ route('admin.cloudflare.dns-records', ['domain' => $domain]) }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Records
                            </a>
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
                    <i class="fa fa-info-circle"></i> Current Record
                </h4>
            </div>
            <div class="panel-body">
                <dl class="dl-horizontal">
                    <dt>Type:</dt>
                    <dd><span class="label label-primary">{{ $record['type'] }}</span></dd>
                    
                    <dt>Name:</dt>
                    <dd><strong>{{ $record['name'] }}</strong></dd>
                    
                    <dt>Content:</dt>
                    <dd><code>{{ $record['content'] }}</code></dd>
                    
                    <dt>TTL:</dt>
                    <dd>{{ $record['ttl'] == 1 ? 'Auto' : $record['ttl'] . 's' }}</dd>
                    
                    @if(isset($record['priority']))
                        <dt>Priority:</dt>
                        <dd>{{ $record['priority'] }}</dd>
                    @endif
                    
                    <dt>Created:</dt>
                    <dd>{{ date('Y-m-d H:i:s', strtotime($record['created_on'])) }}</dd>
                    
                    <dt>Modified:</dt>
                    <dd>{{ date('Y-m-d H:i:s', strtotime($record['modified_on'])) }}</dd>
                </dl>
            </div>
        </div>
        
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-info-circle"></i> Help
                </h4>
            </div>
            <div class="panel-body">
                <h5>Record Types:</h5>
                <ul>
                    <li><strong>A:</strong> Points to IPv4 address (e.g., 192.168.1.1)</li>
                    <li><strong>AAAA:</strong> Points to IPv6 address</li>
                    <li><strong>CNAME:</strong> Points to another domain name</li>
                    <li><strong>MX:</strong> Mail server (requires priority)</li>
                    <li><strong>TXT:</strong> Text data (SPF, DKIM, etc.)</li>
                    <li><strong>NS:</strong> Name server</li>
                </ul>
                
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i> 
                    <strong>Important:</strong> DNS changes may take time to propagate globally.
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
function updateFormFields() {
    const recordType = document.getElementById('recordType').value;
    const contentField = document.getElementById('contentField');
    const contentHelp = document.getElementById('contentHelp');
    const priorityField = document.getElementById('priorityField');
    
    // Reset
    priorityField.style.display = 'none';
    
    switch(recordType) {
        case 'A':
            contentField.placeholder = '192.168.1.1';
            contentHelp.textContent = 'Enter IPv4 address (e.g., 192.168.1.1)';
            break;
        case 'AAAA':
            contentField.placeholder = '2001:db8::1';
            contentHelp.textContent = 'Enter IPv6 address (e.g., 2001:db8::1)';
            break;
        case 'CNAME':
            contentField.placeholder = 'example.com';
            contentHelp.textContent = 'Enter domain name (e.g., example.com)';
            break;
        case 'MX':
            contentField.placeholder = 'mail.example.com';
            contentHelp.textContent = 'Enter mail server domain (e.g., mail.example.com)';
            priorityField.style.display = 'block';
            break;
        case 'TXT':
            contentField.placeholder = 'v=spf1 include:_spf.example.com ~all';
            contentHelp.textContent = 'Enter text content (SPF, DKIM, verification, etc.)';
            break;
        case 'NS':
            contentField.placeholder = 'ns1.example.com';
            contentHelp.textContent = 'Enter name server domain (e.g., ns1.example.com)';
            break;
        default:
            contentField.placeholder = '';
            contentHelp.textContent = 'Enter the target value for this record';
    }
}

// Initialize form fields on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFormFields();
});
</script>
@endsection