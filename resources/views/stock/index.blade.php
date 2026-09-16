<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AHGO Stock Management</title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1a1a2e">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="/icon-512.png">

<style>
  /* ── Reset & Base ── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #1a1a2e; font-size: 13px; }

  /* ── Header ── */
  .header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
    color: #fff; padding: 14px 24px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 3px 12px rgba(0,0,0,0.3);
    position: sticky; top: 0; z-index: 100;
  }
  .header h1 { font-size: 20px; letter-spacing: 1px; font-weight: 700; }
  .header h1 span { color: #e94560; }
  .header-right { display: flex; gap: 12px; align-items: center; }
  .header-link, .btn-logout {
    border: none; border-radius: 6px; padding: 7px 12px; cursor: pointer;
    font-weight: 600; font-size: 12px; text-decoration: none;
  }
  .header-link { background: #3b82f6; color: #fff; }
  .header-link:hover { background: #2563eb; }
  .btn-logout { background: rgba(255,255,255,0.12); color: #fff; }
  .btn-logout:hover { background: rgba(255,255,255,0.2); }
  .btn-add-new {
    background: #e94560; color: #fff; border: none; padding: 7px 16px;
    border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;
    transition: background 0.2s;
  }
  .btn-add-new:hover { background: #c73652; }
  #live-clock { font-size: 12px; opacity: 0.8; }

  /* ── Flash messages ── */
  .flash { padding: 10px 20px; margin: 10px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; }
  .flash-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .flash-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

  /* ── Page layout ── */
  .page { padding: 14px 16px; display: flex; flex-direction: column; gap: 14px; }

  /* ── Summary Cards ── */
  .summary-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
  .card {
    background: #fff; border-radius: 10px; padding: 14px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07); border-top: 4px solid #ccc;
  }
  .card-60  { border-color: #3b82f6; }
  .card-70  { border-color: #10b981; }
  .card-80  { border-color: #f59e0b; }
  .card-all { border-color: #e94560; }
  .card-label { font-size: 11px; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
  .card-value { font-size: 26px; font-weight: 800; margin: 4px 0 2px; }
  .card-sub   { font-size: 11px; color: #555; }
  .card-60  .card-value { color: #3b82f6; }
  .card-70  .card-value { color: #10b981; }
  .card-80  .card-value { color: #f59e0b; }
  .card-all .card-value { color: #e94560; }
  .card-size { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
  .card-size:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

  /* ── Focus Mode ── */
  .focus-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.7); z-index: 900;
    backdrop-filter: blur(5px);
  }
  .focus-overlay.active { display: block; }

  .section.focused {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 95vw; height: 90vh;
    z-index: 1000; background: #fff;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    display: flex; flex-direction: column;
    animation: focusEntry 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  @keyframes focusEntry {
    from { transform: translate(-50%, -50%) scale(0.9); opacity: 0; }
    to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  }

  .section.focused .section-header { padding: 16px 24px; font-size: 18px; }
  .section.focused table { flex: 1; display: table; width: 100%; border-collapse: collapse; }
  .section.focused .table-container { flex: 1; overflow-y: auto; }
  .section.focused thead th { position: sticky; top: 0; z-index: 10; background: #f8f9fa; }

  .focus-close {
    display: none; width: 28px; height: 28px;
    background: rgba(255,255,255,0.2); color: #fff;
    border-radius: 50%; align-items: center; justify-content: center;
    font-size: 20px; cursor: pointer; transition: background 0.2s;
    line-height: 1;
  }
  .focus-close:hover { background: rgba(255,255,255,0.4); }
  .section.focused .focus-close { display: flex; }


  /* Search / lookup */
  .lookup-panel {
    background: #fff; border-radius: 10px; padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    display: grid; grid-template-columns: 260px 1fr; gap: 14px; align-items: start;
  }
  .lookup-input label { display: block; font-size: 12px; font-weight: 700; color: #555; margin-bottom: 5px; }
  .lookup-input input {
    width: 100%; padding: 9px 10px; border: 1px solid #ddd; border-radius: 6px;
    font-size: 13px; outline: none; transition: border 0.2s;
  }
  .lookup-input input:focus { border-color: #3b82f6; }
  .lookup-results { display: grid; grid-template-columns: repeat(5, minmax(90px, 1fr)); gap: 8px; }
  .lookup-result {
    background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px;
    padding: 9px 10px; min-height: 50px;
  }
  .lookup-result-label { color: #777; font-size: 10px; font-weight: 700; text-transform: uppercase; }
  .lookup-result-value { color: #1a1a2e; font-size: 14px; font-weight: 800; margin-top: 3px; word-break: break-word; }
  .lookup-empty { color: #777; display: flex; align-items: center; font-weight: 600; }
  tr.lookup-match { background: #fca5a5 !important; }

  /* ── Stock Tables Section ── */
  .category-row { margin-bottom: 30px; }
  .category-header {
    font-size: 15px; font-weight: 800; color: #1e293b;
    margin-bottom: 12px; padding: 4px 0;
    display: flex; align-items: center; gap: 8px;
    border-bottom: 2px solid #e2e8f0;
  }
  .stock-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 14px; }

  .section { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; }
  .section-header {
    padding: 10px 16px; font-weight: 700; font-size: 13px;
    display: flex; align-items: center; justify-content: space-between; color: #fff;
  }
  .sh-extra { background: #8b5cf6; }

  table { width: 100%; border-collapse: collapse; }
  thead th {
    background: #f8f9fa; padding: 7px 10px; text-align: left;
    font-size: 11px; font-weight: 700; color: #555; border-bottom: 2px solid #e9ecef;
    text-transform: uppercase; letter-spacing: 0.4px;
  }
  tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
  tbody tr:hover { background: #fafbff; }
  tbody td { padding: 6px 10px; vertical-align: middle; }

  .badge-qty {
    display: inline-flex; align-items: center; justify-content: center;
    background: #e8f4fd; color: #1d6fa4; font-weight: 700; font-size: 12px;
    border-radius: 4px; padding: 2px 8px; min-width: 28px;
  }
  .badge-qty.zero { background: #fde8e8; color: #c0392b; }

  /* ── Inline action form ── */
  .action-form { display: flex; align-items: center; gap: 4px; }
  .action-form input[type=number] {
    width: 44px; padding: 3px 5px; border: 1px solid #ddd; border-radius: 4px;
    font-size: 12px; text-align: center;
  }
  .btn-add, .btn-sub {
    border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer;
    font-weight: 700; font-size: 13px; line-height: 1; transition: all 0.15s;
  }
  .btn-add { background: #d4edda; color: #155724; }
  .btn-add:hover { background: #28a745; color: #fff; }
  .btn-sub { background: #f8d7da; color: #721c24; }
  .btn-sub:hover { background: #dc3545; color: #fff; }
  .btn-del { background: none; border: none; color: #ccc; cursor: pointer; font-size: 14px; padding: 2px 4px; }
  .btn-del:hover { color: #dc3545; }

  .tfoot-row td {
    background: #f8f9fa; font-weight: 700; font-size: 12px;
    padding: 7px 10px; border-top: 2px solid #e9ecef;
  }

  /* ── Extras Section ── */
  .extras-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; }

  /* ── Activity Log ── */
  .log-section { background: #1a1a2e; border-radius: 10px; overflow: hidden; }
  .log-header {
    background: #16213e; padding: 10px 16px; color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
  }
  .log-header h3 { font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
  .log-header h3::before { content: '⚡'; }
  .log-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
  .log-date-form { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
  .log-date-field { display: flex; align-items: center; gap: 4px; color: #aaa; font-size: 11px; font-weight: 700; }
  .log-date-form input[type=date] {
    height: 28px; width: 135px; padding: 4px 8px;
    border: 1px solid rgba(255,255,255,0.18); border-radius: 6px;
    background: #1a1a2e; color: #fff; font-size: 12px; outline: none;
  }
  .log-date-form input[type=date]:focus { border-color: #3b82f6; }
  .btn-log-search, .btn-log-clear {
    height: 28px; border: none; border-radius: 6px; padding: 0 10px;
    font-size: 11px; font-weight: 700; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .btn-log-search { background: #3b82f6; color: #fff; }
  .btn-log-search:hover { background: #2563eb; }
  .btn-log-clear { background: rgba(255,255,255,0.1); color: #ccc; }
  .btn-log-clear:hover { background: rgba(255,255,255,0.18); color: #fff; }
  .log-scroll { max-height: 280px; overflow-y: auto; padding: 8px 0; }
  .log-scroll::-webkit-scrollbar { width: 4px; }
  .log-scroll::-webkit-scrollbar-track { background: #1a1a2e; }
  .log-scroll::-webkit-scrollbar-thumb { background: #0f3460; border-radius: 4px; }

  .log-item {
    display: flex; align-items: center; padding: 7px 16px; gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.1s;
  }
  .log-item:hover { background: rgba(255,255,255,0.04); }
  .log-icon { font-size: 16px; flex-shrink: 0; }
  .log-body { flex: 1; }
  .log-title { color: #e0e0e0; font-size: 12px; }
  .log-title strong { color: #fff; }
  .log-time { color: #888; font-size: 11px; margin-top: 1px; }
  .badge-add  { background: rgba(16,185,129,0.15); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
  .badge-sub  { background: rgba(239,68,68,0.15);  color: #ef4444; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
  .badge-dispatch { background: rgba(245,158,11,0.18); color: #f59e0b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
  .log-qty { font-size: 11px; color: #aaa; flex-shrink: 0; text-align: right; }
  .log-empty { text-align: center; color: #555; padding: 30px; font-size: 12px; }

  /* ── Modal ── */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center;
  }
  .modal-overlay.active { display: flex; }
  .modal {
    background: #fff; border-radius: 12px; padding: 24px;
    width: 400px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  }
  .modal h2 { font-size: 16px; margin-bottom: 16px; color: #1a1a2e; }
  .form-group { margin-bottom: 12px; }
  .form-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 4px; }
  .form-group input, .form-group select {
    width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px;
    font-size: 13px; outline: none; transition: border 0.2s;
  }
  .form-group input:focus, .form-group select:focus { border-color: #3b82f6; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .modal-btns { display: flex; gap: 10px; margin-top: 16px; }
  .btn-submit { flex: 1; background: #e94560; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13px; }
  .btn-submit:hover { background: #c73652; }
  .btn-cancel { flex: 1; background: #f0f2f5; color: #555; border: none; padding: 10px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13px; }
  .btn-cancel:hover { background: #ddd; }

  .error-msg { color: #dc3545; font-size: 11px; margin-top: 4px; }

  .editable-nwt { cursor: pointer; color: #3b82f6; font-weight: 600; transition: background 0.2s; border-radius: 4px; }
  .editable-nwt:hover { background: #eff6ff; text-decoration: underline; }
  .editable-nwt input { width: 60px; padding: 2px 4px; border: 1px solid #3b82f6; border-radius: 4px; font-size: 12px; font-weight: 600; text-align: left; }

  @media (max-width: 1100px) {
    .stock-grid { grid-template-columns: 1fr 1fr; }
    .lookup-panel { grid-template-columns: 1fr; }
  }
  @media (max-width: 700px) {
    .stock-grid { grid-template-columns: 1fr; }
    .summary-row { grid-template-columns: 1fr 1fr; }
    .lookup-results { grid-template-columns: 1fr 1fr; }
    .log-header { align-items: flex-start; }
    .log-tools { width: 100%; justify-content: flex-start; }
  }

  /* ── PWA Install Banner ── */
  .install-banner {
    display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    width: 90%; max-width: 400px; background: #fff; border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 2000;
    padding: 16px; align-items: center; gap: 14px;
    border-left: 4px solid #e94560;
    animation: slideUp 0.5s ease-out;
  }
  @keyframes slideUp {
    from { bottom: -100px; opacity: 0; }
    to { bottom: 20px; opacity: 1; }
  }
  .install-banner.active { display: flex; }
  .install-icon { width: 48px; height: 48px; background: #1a1a2e; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
  .install-content { flex: 1; }
  .install-content h4 { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
  .install-content p { font-size: 11px; color: #666; }
  .install-actions { display: flex; gap: 8px; }
  .btn-install { background: #e94560; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; }
  .btn-install-close { background: #f0f2f5; color: #555; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; }

</style>
</head>
<body>
@php
  $canManageStock = auth()->user()?->isAdmin() ?? false;
@endphp

<!-- PWA Install Banner -->
<div class="install-banner" id="pwaInstallBanner">
  <div class="install-icon">📦</div>
  <div class="install-content">
    <h4>Install AHGO Stock</h4>
    <p>Get quick access from your home screen.</p>
  </div>
  <div class="install-actions">
    <button class="btn-install" id="btnPwaInstall">Install</button>
    <button class="btn-install-close" onclick="dismissPwaBanner()">Not now</button>
  </div>
</div>

<!-- Header -->
<div class="header">
  <h1>AHGO <span>STOCK</span> MANAGEMENT</h1>
  <div class="header-right">
    <span id="live-clock"></span>
    @if($canManageStock)
      <a class="header-link" href="{{ route('members.index') }}">Members</a>
      <button class="btn-add-new" onclick="openModal()">+ Add New Item</button>
    @endif
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn-logout" type="submit">Logout</button>
    </form>
  </div>
</div>

<div class="focus-overlay" id="focusOverlay" onclick="closeFocus()"></div>

<div class="page">


  <!-- Bag lookup -->
  <div class="lookup-panel">
    <div class="lookup-input">
      <label>Find Bag / Item No.</label>
      <input type="text" id="bagLookupInput" placeholder="Type bag no..." autocomplete="off">
    </div>
    <div id="bagLookupResults" class="lookup-empty"></div>
  </div>

  <!-- Summary Cards -->
  @php
    $cardColors = ['#3b82f6', '#10b981', '#f59e0b', '#e94560', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'];
  @endphp
  <div class="summary-row">
    @foreach($summaryGroups as $size => $items)
      @php
        $color = $cardColors[$loop->index % count($cardColors)];
        $sizeTotal = $totals[$size] ?? ['bags'=>0, 'nwt'=>0];
      @endphp
      <div class="card card-size" data-summary-size="{{ $size }}" style="border-color: {{ $color }}">
        <div class="card-label">{{ $size }}</div>
        <div class="card-value" style="color: {{ $color }}">{{ $sizeTotal['bags'] }}</div>
        <div class="card-sub">Total: {{ number_format($sizeTotal['nwt'],2) }}{{ str_contains(strtoupper($size), 'BND') ? '' : ' kg' }}</div>
      </div>
    @endforeach
  </div>

  <div class="category-row">
    <div class="category-header">📦 Bags</div>
    <div class="stock-grid">
      @foreach($bagGroups as $size => $items)
        @include('stock.partials.panel', ['size' => $size, 'items' => $items, 'category' => 'BAG', 'icon' => '📦', 'colorIndex' => $loop->index])
      @endforeach
    </div>
  </div>

  <div class="category-row">
    <div class="category-header">🧵 Bundles (BND)</div>
    <div class="stock-grid">
      @foreach($bndGroups as $size => $items)
        @include('stock.partials.panel', ['size' => $size, 'items' => $items, 'category' => 'BND', 'icon' => '🧵', 'colorIndex' => $loop->index])
      @endforeach
    </div>
  </div>

  <div class="category-row">
    <div class="category-header">🔳 Square Fit (SQF)</div>
    <div class="stock-grid">
      @foreach($sqfGroups as $size => $items)
        @include('stock.partials.panel', ['size' => $size, 'items' => $items, 'category' => 'SQF', 'icon' => '🔳', 'colorIndex' => $loop->index])
      @endforeach
    </div>
  </div>

  <!-- Other Items -->
  <div class="extras-section">
    <div class="section-header sh-extra">
      <span>📋 Other Items</span>
      <span>{{ $extras->count() }} items</span>
    </div>
    <table>
      <thead>
        <tr><th>#</th><th>Category</th><th>Item No</th><th>Size</th><th>NWT</th><th>Qty</th>@if($canManageStock)<th>Action</th><th></th>@endif</tr>
      </thead>
      <tbody>
        @foreach($extras as $i => $s)
        <tr
          data-stock-id="{{ $s->id }}"
          data-bag-no="{{ $s->bag_no }}"
          data-category="@if($s->category === 'bag')Bag@elseif($s->category === 'bnd')BND@elseif($s->category === 'sqf')SQF@else{{ strtoupper($s->category) }}@endif"
          data-size="{{ $s->size }}"
          data-nwt="{{ number_format($s->nwt,2) }}"
        >
          <td style="color:#aaa">{{ $i+1 }}</td>
          <td>
            <strong>
              @if($s->category === 'bag')
                Bag
              @elseif($s->category === 'bnd')
                BND
              @elseif($s->category === 'sqf')
                SQF
              @else
                {{ strtoupper($s->category) }}
              @endif
            </strong>
          </td>
          <td>{{ $s->bag_no }}</td>
          <td>{{ $s->size }}</td>
          <td class="{{ $s->category === 'bag' ? 'editable-nwt' : '' }}" data-stock-id="{{ $s->id }}">{{ number_format($s->nwt,2) }}</td>
          <td><span class="badge-qty {{ $s->quantity==0?'zero':'' }}">{{ $s->quantity }}</span></td>
          @if($canManageStock)
            <td>
              <form method="POST" action="{{ route('stock.update',$s->id) }}" class="action-form" data-stock-id="{{ $s->id }}">
                @csrf @method('PATCH')
                <input type="hidden" name="quantity" value="1">
                <button class="btn-sub" name="action" value="SUBTRACT" title="Subtract">−</button>
                <button class="btn-add" name="action" value="ADD" title="Add">+</button>
              </form>
            </td>
            <td>
              <form method="POST" action="{{ route('stock.destroy',$s->id) }}" data-stock-id="{{ $s->id }}" data-bag-no="{{ $s->bag_no }}">
                @csrf @method('DELETE')
                <button class="btn-del" title="Delete">✕</button>
              </form>
            </td>
          @endif
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Activity Log -->
  <div class="log-section">
    <div class="log-header">
      <h3>Activity Log</h3>
      <div class="log-tools">
        <form method="GET" action="{{ route('stock.index') }}" class="log-date-form">
          <label class="log-date-field">
            From
            <input type="date" name="from_date" value="{{ $fromDate }}" max="{{ now()->toDateString() }}">
          </label>
          <label class="log-date-field">
            To
            <input type="date" name="to_date" value="{{ $toDate }}" max="{{ now()->toDateString() }}">
          </label>
          <button type="submit" class="btn-log-search">Search</button>
          @if($fromDate || $toDate)
            <a href="{{ route('stock.index') }}" class="btn-log-clear">Clear</a>
          @endif
        </form>
        <span style="color:#888;font-size:11px">
          @if($fromDate && $toDate)
            Showing records from {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
          @elseif($fromDate)
            Showing records from {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}
          @elseif($toDate)
            Showing records up to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
          @else
            Last 50 updates
          @endif
        </span>
      </div>
    </div>
    <div class="log-scroll">
      @forelse($logs as $log)
      @php
        $logBagNo = $log->bag_no ?? optional($log->stock)->bag_no ?? 'N/A';
        $logCategory = $log->category ?? optional($log->stock)->category;
        $logSize = $log->size ?? optional($log->stock)->size;
        $logNwt = $log->nwt ?? optional($log->stock)->nwt;
        $logBadgeClass = $log->action === 'ADD' ? 'badge-add' : ($log->action === 'DISPATCH' ? 'badge-dispatch' : 'badge-sub');
        $logIcon = $log->action === 'ADD' ? '➕' : ($log->action === 'DISPATCH' ? '📦' : '➖');
        $logSign = $log->action === 'ADD' ? '+' : '-';
        $logColor = $log->action === 'ADD' ? '#10b981' : ($log->action === 'DISPATCH' ? '#f59e0b' : '#ef4444');
      @endphp
      <div class="log-item">
        <div class="log-icon">{{ $logIcon }}</div>
        <div class="log-body">
          <div class="log-title">
            <span class="{{ $logBadgeClass }}">{{ $log->action }}</span>
            &nbsp;<strong>Item #{{ $logBagNo }}</strong>
            @if($logCategory || $logSize || $logNwt)
              <span style="color:#aaa">
                ({{ strtoupper($logCategory ?? 'ITEM') }}@if($logSize), Size {{ $logSize }}@endif @if($logNwt), NWT {{ number_format((float) $logNwt, 2) }} kg @endif)
              </span>
            @endif
            @if($log->note) — <span class="log-note-text" style="color:#f1f5f9;font-weight:500;">{{ $log->note }}</span>@endif
          </div>
          <div class="log-time">🕐 {{ $log->logged_at->format('d M Y, h:i:s A') }}</div>
        </div>
        <div class="log-qty">
          <div style="color:#aaa;font-size:10px">Before → After</div>
          <div style="color:#fff;font-weight:700">{{ $log->quantity_before }} → {{ $log->quantity_after }}</div>
          <div style="font-size:10px;color:{{ $logColor }}">
            {{ $logSign }}{{ $log->quantity_changed }}
          </div>
        </div>
      </div>
      @empty
      <div class="log-empty">No activity yet. Start by adding or subtracting stock above.</div>
      @endforelse
    </div>
  </div>

</div>{{-- end .page --}}

<!-- Add New Item Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <h2>➕ Add New Stock Item</h2>
    <form id="addStockForm" method="POST" action="{{ route('stock.store') }}">
      @csrf
      <div class="form-group">
        <label>Category</label>
        <select name="category" id="categorySelect">
          <option value="bag">Bag (Bag)</option>
          <option value="bnd">BND (Bundle)</option>
          <option value="sqf">SQF (Square Fit)</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Bag / Item No.</label>
          <input type="text" name="bag_no" id="bagNoInput" placeholder="e.g. 13200" required>
        </div>
        <div class="form-group" id="sizeGroup">
          <label>Size</label>
          <input type="text" name="size" placeholder="e.g. 0.60/75/400" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label id="nwtLabel">Net Weight (NWT)</label>
          <input type="number" name="nwt" step="0.01" min="0" value="0" required>
        </div>
        <div class="form-group">
          <label>Quantity</label>
          <input type="number" name="quantity" value="1" min="1" required>
        </div>
      </div>
      <div class="modal-btns">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-submit">Add Item</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Item Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <h2 style="color:#ef4444;display:flex;align-items:center;gap:8px;">
      <span>🗑️</span> Delete Stock Item
    </h2>
    <p id="deleteItemPromptText" style="color:#444;font-size:13px;margin-bottom:14px;line-height:1.5;">
      Are you sure you want to delete this item?
    </p>
    <form id="deleteStockForm">
      <div class="form-group">
        <label for="deleteNoteInput" style="display:block;font-size:12px;font-weight:600;color:#333;margin-bottom:6px;">
          Note / Reason for deletion <span style="color:#ef4444">*</span>
        </label>
        <textarea id="deleteNoteInput" rows="3" placeholder="Enter reason or note (e.g. Dispatched to customer, Damaged, Expired, Stock correction...)" required style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;outline:none;resize:vertical;font-family:inherit;box-sizing:border-box;"></textarea>
        <div id="deleteNoteError" class="error-msg" style="display:none;color:#ef4444;font-size:11px;margin-top:4px;">Please enter a note / reason before deleting.</div>
      </div>
      <div class="modal-btns">
        <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
        <button type="submit" class="btn-submit" id="confirmDeleteBtn" style="background:#ef4444;">Confirm Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
// Live clock
function updateClock() {
  const now = new Date();
  document.getElementById('live-clock').textContent =
    now.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) + ' ' +
    now.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);

// Focus Mode logic
function openFocus(fullKey) {
  const section = document.querySelector(`.section[data-full-key="${fullKey}"]`);
  if (!section) return;

  document.getElementById('focusOverlay').classList.add('active');
  
  // Create a container for the table if not already present (to allow scrolling in focused mode)
  if (!section.querySelector('.table-container')) {
    const table = section.querySelector('table');
    const container = document.createElement('div');
    container.className = 'table-container';
    table.parentNode.insertBefore(container, table);
    container.appendChild(table);
  }

  section.classList.add('focused');
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeFocus(e) {
  if (e) e.stopPropagation();
  
  const focusedSection = document.querySelector('.section.focused');
  if (focusedSection) {
    focusedSection.classList.remove('focused');
  }
  
  document.getElementById('focusOverlay').classList.remove('active');
  document.body.style.overflow = '';
}

// Add click listeners to summary cards
document.querySelectorAll('.card-size').forEach(card => {
  card.addEventListener('click', function() {
    const fullKey = this.dataset.summarySize;
    openFocus(fullKey);
  });
});

// Modal
function openModal()  { 
  document.getElementById('addModal').classList.add('active'); 
  updateModalLabels();
}
function closeModal() { document.getElementById('addModal').classList.remove('active'); }

// Delete Modal
let currentDeleteForm = null;

function openDeleteModal(form, bagNo, details = '') {
  currentDeleteForm = form;
  const promptEl = document.getElementById('deleteItemPromptText');
  if (promptEl) {
    promptEl.innerHTML = `Are you sure you want to delete <strong>Item #${escapeHtml(bagNo)}</strong>${details ? ` <span style="color:#666">(${escapeHtml(details)})</span>` : ''}? Please enter a note for the activity log:`;
  }
  const noteInput = document.getElementById('deleteNoteInput');
  if (noteInput) {
    noteInput.value = '';
    const errorEl = document.getElementById('deleteNoteError');
    if (errorEl) errorEl.style.display = 'none';
  }
  document.getElementById('deleteModal').classList.add('active');
  setTimeout(() => {
    if (noteInput) noteInput.focus();
  }, 100);
}

function closeDeleteModal() {
  const deleteModal = document.getElementById('deleteModal');
  if (deleteModal) deleteModal.classList.remove('active');
  currentDeleteForm = null;
}

function updateModalLabels() {
  const category = document.getElementById('categorySelect').value;
  const nwtLabel = document.getElementById('nwtLabel');
  const bagNoInput = document.getElementById('bagNoInput');
  
  if (nwtLabel) {
    if (category === 'bnd') {
      nwtLabel.textContent = 'Bundle';
    } else if (category === 'sqf') {
      nwtLabel.textContent = 'Square Fit';
    } else {
      nwtLabel.textContent = 'Net Weight (NWT)';
    }
  }

  if (bagNoInput) {
    if (category === 'bnd' || category === 'sqf' || category === 'other') {
      bagNoInput.removeAttribute('required');
      bagNoInput.placeholder = 'Optional for ' + (category === 'bnd' ? 'Bundle' : (category === 'sqf' ? 'Square Fit' : 'Other'));
    } else {
      bagNoInput.setAttribute('required', 'required');
      bagNoInput.placeholder = 'e.g. 13200';
    }
  }
}

document.getElementById('categorySelect').addEventListener('change', updateModalLabels);

document.getElementById('addModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    if (document.getElementById('deleteModal')?.classList.contains('active')) {
      closeDeleteModal();
    } else if (document.getElementById('addModal')?.classList.contains('active')) {
      closeModal();
    }
  }
});

document.getElementById('deleteNoteInput')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    document.getElementById('deleteStockForm')?.requestSubmit();
  }
});

// Show flash message
function showFlash(message, type = 'success') {
  console[type === 'success' ? 'log' : 'error'](message);
}

function getRowQuantity(row) {
  return row.querySelector('.badge-qty')?.textContent.trim() || '0';
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderLookupResult(row) {
  const fields = [
    ['Category', row.dataset.category || 'N/A'],
    ['Bag / Item No.', row.dataset.bagNo || 'N/A'],
    ['Size', row.dataset.size || 'N/A'],
    ['NWT', (row.dataset.nwt || '0.00') + ' kg'],
    ['Quantity', getRowQuantity(row)],
  ];

  return fields.map(([label, value]) => `
    <div class="lookup-result">
      <div class="lookup-result-label">${escapeHtml(label)}</div>
      <div class="lookup-result-value">${escapeHtml(value)}</div>
    </div>
  `).join('');
}

function updateBagLookup() {
  const input = document.getElementById('bagLookupInput');
  const results = document.getElementById('bagLookupResults');
  if (!input || !results) return;

  const query = input.value.trim().toLowerCase();
  const rows = Array.from(document.querySelectorAll('tbody tr[data-bag-no]'));

  rows.forEach(row => row.classList.remove('lookup-match'));

  if (!query) {
    results.className = 'lookup-empty';
    results.textContent = '';
    return;
  }

  const matches = rows.filter(row => (row.dataset.bagNo || '').toLowerCase().includes(query));

  if (!matches.length) {
    results.className = 'lookup-empty';
    results.textContent = 'No matching bag or item found.';
    return;
  }

  const exactMatch = matches.find(row => (row.dataset.bagNo || '').toLowerCase() === query);
  const selectedMatch = exactMatch || matches[0];

  matches.forEach(row => row.classList.add('lookup-match'));
  results.className = 'lookup-results';
  results.innerHTML = renderLookupResult(selectedMatch);

  if (matches.length === 1) {
    selectedMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

// Auto-dismiss flash
setTimeout(() => {
  document.querySelectorAll('.flash').forEach(el => el.style.display='none');
}, 4000);

// AJAX form submission for ADD/SUBTRACT operations
document.addEventListener('submit', function(e) {
  if (e.target.classList.contains('action-form')) {
    e.preventDefault();
    
    const form = e.target;
    const url = form.getAttribute('action');
    const quantity = form.querySelector('input[name="quantity"]').value;
    const action = e.submitter.value;
    const note = form.querySelector('input[name="note"]')?.value || '';
    const stockId = form.getAttribute('data-stock-id');
    
    const formData = new FormData();
    formData.append('action', action);
    formData.append('quantity', quantity);
    formData.append('note', note);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showFlash(data.message, 'success');
        // Update quantity badge and recalculate all totals immediately
        updateStockRow(stockId, data.quantity_after);
      } else {
        showFlash(data.message, 'error');
      }
    })
    .catch(error => {
      showFlash('An error occurred', 'error');
      console.error('Error:', error);
    });
  }
  
  // Add new item form
  if (e.target.id === 'addStockForm') {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(e.target);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(form.getAttribute('action'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(async response => {
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        throw new Error('Server returned HTML instead of JSON. Check the form action URL.');
      }

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Error adding item');
      }

      return data;
    })
    .then(data => {
      if (data.success) {
        showFlash(data.message, 'success');
        form.reset();
        closeModal();
        // Do full page reload for new items to show in correct table
        setTimeout(() => location.reload(), 300);
      } else {
        showFlash(data.message || 'Error adding item', 'error');
      }
    })
    .catch(error => {
      showFlash(error.message || 'An error occurred', 'error');
      console.error('Error:', error);
    });
  }
});

// Delete with Modal and required Note
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('btn-del')) {
    e.preventDefault();
    
    const form = e.target.closest('form');
    if (!form) return;
    
    const row = form.closest('tr');
    const bagNo = form.getAttribute('data-bag-no') || row?.getAttribute('data-bag-no') || row?.querySelector('strong')?.textContent.trim() || 'Item';
    
    let details = '';
    if (row) {
      const cat = row.getAttribute('data-category');
      const size = row.getAttribute('data-size');
      const nwt = row.getAttribute('data-nwt');
      const parts = [];
      if (cat) parts.push(cat);
      if (size) parts.push('Size ' + size);
      if (nwt && parseFloat(nwt) > 0) parts.push('NWT ' + nwt + ' kg');
      if (parts.length > 0) details = parts.join(', ');
    }
    
    openDeleteModal(form, bagNo, details);
  }
});

document.getElementById('deleteStockForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  
  if (!currentDeleteForm) return;
  
  const noteInput = document.getElementById('deleteNoteInput');
  const note = noteInput ? noteInput.value.trim() : '';
  const errorEl = document.getElementById('deleteNoteError');
  
  if (!note) {
    if (errorEl) errorEl.style.display = 'block';
    if (noteInput) noteInput.focus();
    return;
  }
  
  const submitBtn = document.getElementById('confirmDeleteBtn');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Deleting...';
  }
  
  const url = currentDeleteForm.getAttribute('action');
  const formData = new FormData();
  formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
  formData.append('_method', 'DELETE');
  formData.append('note', note);
  
  const row = currentDeleteForm.closest('tr');
  
  fetch(url, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
  .then(response => response.json())
  .then(data => {
    closeDeleteModal();
    if (data.success) {
      showFlash(data.message, 'success');
      if (row) {
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.3s';
      }
      setTimeout(() => {
        location.reload();
      }, 300);
    } else {
      alert(data.message || 'Error deleting item');
    }
  })
  .catch(error => {
    alert('An error occurred while deleting the item.');
    console.error('Error:', error);
  })
  .finally(() => {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Confirm Delete';
    }
  });
});

// Update a single row's quantity display and recalculate all totals
function updateStockRow(stockId, newQuantity) {
  console.log('Updating stock ID:', stockId, 'to quantity:', newQuantity);
  
  const rows = document.querySelectorAll(`[data-stock-id="${stockId}"]`);
  console.log('Found rows:', rows.length);
  
  rows.forEach(row => {
    const badgeQty = row.querySelector('.badge-qty');
    if (badgeQty) {
      badgeQty.textContent = newQuantity;
      badgeQty.classList.toggle('zero', newQuantity == 0);
      console.log('Updated badge to:', newQuantity);
    }
  });
  
  // Recalculate all totals immediately
  recalculateAllTotals();
}

// Recalculate all table totals and summary cards from current DOM
function recalculateAllTotals() {
  console.log('Starting recalculation...');
  
  const totals = {};
  
  // Find all stock grid sections
  document.querySelectorAll('.stock-grid .section').forEach(section => {
    const firstRow = section.querySelector('tbody tr[data-size]');
    const size = firstRow?.dataset.size;
    if (!size) return;
    
    console.log('Processing size:', size);
    
    // Calculate totals for this size
    const table = section.querySelector('table');
    if (!table) return;
    
    let totalBags = 0;
    let totalNwt = 0;
    
    table.querySelectorAll('tbody tr').forEach(row => {
      // Get quantity from badge
      const qtyBadge = row.querySelector('.badge-qty');
      if (!qtyBadge) return;
      
      const qty = parseInt(qtyBadge.textContent) || 0;
      
      // Get NWT from cell with data-stock-id or third cell
      const nwtCell = row.querySelector('.editable-nwt') || row.cells[2];
      if (!nwtCell) return;
      
      const nwtText = nwtCell.textContent.trim();
      const nwt = parseFloat(nwtText) || 0;
      
      totalBags += qty;
      totalNwt += (nwt * qty);
    });
    
    console.log(`Size ${size}: bags=${totalBags}, nwt=${totalNwt.toFixed(2)}`);
    totals[size] = { bags: totalBags, nwt: totalNwt };
    
    // Update this table's footer
    const footerRow = table.querySelector('tfoot tr');
    if (footerRow && footerRow.cells.length > 0) {
      // Find and update the NWT cell
      const nwtFootCell = Array.from(footerRow.cells).find(cell => cell.textContent.includes('kg'));
      if (nwtFootCell) {
        nwtFootCell.textContent = totalNwt.toFixed(2) + ' kg';
        console.log('Updated footer NWT to:', totalNwt.toFixed(2));
      }
      
      // Find and update the bags cell
      const bagsFootCell = Array.from(footerRow.cells).find(cell => cell.textContent.includes('bags'));
      if (bagsFootCell) {
        bagsFootCell.textContent = totalBags + ' bags';
        console.log('Updated footer bags to:', totalBags);
      }
    }
  });
  
  // Update summary cards
  updateSummaryCards(totals);
  
  // Refresh activity log
  refreshActivityLog();
}

// Update summary cards with calculated totals
function updateSummaryCards(totals) {
  console.log('Updating summary cards with totals:', totals);

  document.querySelectorAll('[data-summary-size]').forEach(card => {
    const size = card.dataset.summarySize;
    const total = totals[size] || { bags: 0, nwt: 0 };
    const value = card.querySelector('.card-value');
    const sub = card.querySelector('.card-sub');

    if (value) {
      value.textContent = total.bags;
      console.log(`Updated card ${size} value to:`, total.bags);
    }
    if (sub) {
      const isBnd = size.toUpperCase().includes('BND');
      sub.textContent = 'Total: ' + total.nwt.toFixed(2) + (isBnd ? '' : ' kg');
      console.log(`Updated card ${size} sub to:`, total.nwt.toFixed(2));
    }
  });
}

// Refresh activity log from server
function refreshActivityLog() {
  fetch('{{ route('stock.index') }}' + window.location.search, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => response.text())
  .then(html => {
    const parser = new DOMParser();
    const newDoc = parser.parseFromString(html, 'text/html');
    
    // Update activity log
    const logScroll = document.querySelector('.log-scroll');
    const newLogScroll = newDoc.querySelector('.log-scroll');
    if (logScroll && newLogScroll) {
      logScroll.innerHTML = newLogScroll.innerHTML;
    }
  })
  .catch(error => console.error('Error refreshing log:', error));
}

// Refresh summary cards and activity log only
function refreshCardsAndLog() {
  // Recalculate all totals from current DOM
  recalculateAllTotals();
}

// Apply data attributes to forms for AJAX handling
function applyDataAttributes(container = document) {
  container.querySelectorAll('table tbody tr').forEach(tr => {
    const form = tr.querySelector('.action-form');
    if (form) {
      // Extract stock ID from the form action or data attribute
      const stockIdMatch = tr.getAttribute('data-stock-id');
      if (stockIdMatch) {
        form.setAttribute('data-stock-id', stockIdMatch);
      }
    }
    
    const deleteForm = tr.querySelector('form[method="POST"]');
    if (deleteForm && deleteForm.getAttribute('action').includes('DELETE')) {
      const stockIdMatch = deleteForm.getAttribute('action').match(/\/stock\/(\d+)/);
      if (stockIdMatch) {
        deleteForm.setAttribute('data-stock-id', stockIdMatch[1]);
        const bagNo = tr.querySelector('strong')?.textContent || 'Item';
        deleteForm.setAttribute('data-bag-no', bagNo);
      }
    }
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  applyDataAttributes();
  document.getElementById('bagLookupInput')?.addEventListener('input', updateBagLookup);
});

// Inline editing for NWT
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('editable-nwt')) {
    const cell = e.target;
    if (cell.querySelector('input')) return;

    const stockId = cell.getAttribute('data-stock-id');
    const currentValue = cell.textContent.trim();
    const input = document.createElement('input');
    input.type = 'number';
    input.step = '0.01';
    input.value = currentValue;
    
    cell.innerHTML = '';
    cell.appendChild(input);
    input.focus();
    input.select();

    const save = () => {
      const newValue = input.value;
      if (newValue === currentValue || newValue === '') {
        cell.textContent = currentValue;
        return;
      }

      const formData = new FormData();
      formData.append('nwt', newValue);
      formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

      fetch(`/stock/items/${stockId}/nwt`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          cell.textContent = parseFloat(data.new_nwt).toFixed(2);
          showFlash(data.message, 'success');
          recalculateAllTotals();
        } else {
          showFlash(data.message, 'error');
          cell.textContent = currentValue;
        }
      })
      .catch(error => {
        showFlash('Error updating NWT', 'error');
        cell.textContent = currentValue;
      });
    };

    input.addEventListener('blur', save);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        input.blur();
      } else if (e.key === 'Escape') {
        input.value = currentValue;
        input.blur();
      }
    });
  }
});

// PWA Logic
let deferredPrompt;
const installBanner = document.getElementById('pwaInstallBanner');
const installBtn = document.getElementById('btnPwaInstall');

window.addEventListener('beforeinstallprompt', (e) => {
  // Prevent Chrome 67 and earlier from automatically showing the prompt
  e.preventDefault();
  // Stash the event so it can be triggered later.
  deferredPrompt = e;
  
  // Only show banner if user hasn't dismissed it recently
  if (!localStorage.getItem('pwaDismissed')) {
    installBanner.classList.add('active');
  }
});

installBtn.addEventListener('click', (e) => {
  // Hide the app provided install promotion
  installBanner.classList.remove('active');
  // Show the install prompt
  deferredPrompt.prompt();
  // Wait for the user to respond to the prompt
  deferredPrompt.userChoice.then((choiceResult) => {
    if (choiceResult.outcome === 'accepted') {
      console.log('User accepted the install prompt');
    } else {
      console.log('User dismissed the install prompt');
    }
    deferredPrompt = null;
  });
});

function dismissPwaBanner() {
  installBanner.classList.remove('active');
  // Optional: Don't show again for 7 days
  localStorage.setItem('pwaDismissed', Date.now());
}

// Service Worker Registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => console.log('Service Worker registered', reg))
      .catch(err => console.log('Service Worker registration failed', err));
  });
}
</script>

</body>
</html>
