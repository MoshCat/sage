@if ($data['table'])
  @php
  $table       = $data['table'];
  $table_size  = ($data['table_small']) ? ' table-sm' : '';
  $use_borders = ($data['table_use_borders']) ? ' ' . $data['table_use_borders'] : '';
  @endphp
  <div class="table-responsive">
    <table class="table{!! $table_size . $use_borders!!}">
      @if ( $table['header'] )
        <thead class="thead-light">
          <tr>
            @foreach ( $table['header'] as $th )
              <th scope="col">{!! $th['c'] !!}</th>
            @endforeach
          </tr>
        </thead>
      @endif
      <tbody>
        @foreach ( $table['body'] as $tr )
          <tr>
            @foreach ( $tr as $td )
              <td>{!! $td['c'] !!}</td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
