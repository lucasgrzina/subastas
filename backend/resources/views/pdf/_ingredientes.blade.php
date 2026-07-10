<table class="ing-table">
@foreach($lista as $ing)
<tr>
  <td>
    <span class="ing-nombre">{{ $ing->ingredient?->nombre }}</span>
    @if($ing->estado_preparacion)<span class="ing-prep">, {{ $ing->estado_preparacion }}</span>@endif
    @if($ing->opcional)<span class="ing-opc"> (opcional)</span>@endif
  </td>
  <td class="ing-cant">
    @if($ing->cantidad && $ing->unit)
      {{ $ing->cantidad == floor($ing->cantidad) ? (int)$ing->cantidad : $ing->cantidad }}
      {{ $ing->unit->abreviatura }}
    @elseif($ing->cantidad_texto)
      {{ $ing->cantidad_texto }}
    @endif
  </td>
</tr>
@endforeach
</table>
