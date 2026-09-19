@php
  $color = $cardColors[$colorIndex % count($cardColors)];
  $fullKey = strtoupper($category) . ' - ' . $size;
  $sizeTotal = $totals[$fullKey] ?? ['bags'=>0, 'nwt'=>0];
  
  $isBnd = strtoupper($category) === 'BND'; 
  $isSqf = strtoupper($category) === 'SQF';
  $isEditableValue = in_array(strtoupper($category), ['BAG', 'BND'], true);
  $label = 'NWT (kg)';
  if($isBnd) $label = 'Bundle';
  if($isSqf) $label = 'Square Fit';
@endphp

<div class="section bag-section" data-stock-size="{{ $size }}" data-full-key="{{ $fullKey }}">
  <div class="section-header" style="background: {{ $color }}">
    <span>{{ $icon }} {{ $size }}</span>
    <div style="display: flex; align-items: center; gap: 10px;">
      <span>{{ $items->count() }} items</span>
      <span class="focus-close" onclick="closeFocus(event)" title="Close">&times;</span>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Bag No</th>
        <th>{{ $label }}</th>
        <th>Qty</th>
        @if($canManageStock)<th>Action</th><th></th>@endif
      </tr>
    </thead>
    <tbody>
      @foreach($items as $i => $s)
      <tr data-stock-id="{{ $s->id }}" data-bag-no="{{ $s->bag_no }}" data-category="{{ $s->category }}" data-size="{{ $s->size }}" data-nwt="{{ number_format($s->nwt,2) }}">
        <td style="color:#aaa">{{ $i+1 }}</td>
        <td><strong>{{ $s->bag_no }}</strong></td>
        <td class="{{ $isEditableValue ? 'editable-nwt' : '' }}" data-stock-id="{{ $s->id }}" @if($isEditableValue) title="Click to edit" @endif>{{ number_format($s->nwt,2) }}</td>
        <td><span class="badge-qty {{ $s->quantity==0?'zero':'' }}">{{ $s->quantity }}</span></td>
        @if($canManageStock)
          <td>
            <form method="POST" action="{{ route('stock.update',$s->id) }}" class="action-form" data-stock-id="{{ $s->id }}">
              @csrf @method('PATCH')
              <input type="hidden" name="quantity" value="1">
              <button class="btn-sub" name="action" value="SUBTRACT" title="Subtract">-</button>
              <button class="btn-add" name="action" value="ADD" title="Add">+</button>
            </form>
          </td>
          <td>
            <form method="POST" action="{{ route('stock.destroy',$s->id) }}" data-stock-id="{{ $s->id }}" data-bag-no="{{ $s->bag_no }}">
              @csrf @method('DELETE')
              <button class="btn-del" title="Delete">x</button>
            </form>
          </td>
        @endif
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr class="tfoot-row">
        <td colspan="2">TOTAL</td>
        <td>{{ number_format($sizeTotal['nwt'],2) }}{{ $isBnd ? '' : ' kg' }}</td>
        <td>{{ $sizeTotal['bags'] }} items</td>
        @if($canManageStock)<td colspan="2"></td>@endif
      </tr>
    </tfoot>
  </table>
</div>
