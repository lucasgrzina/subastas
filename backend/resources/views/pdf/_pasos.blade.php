<table class="pasos-table">
@foreach($pasos as $paso)
<tr class="{{ $paso->es_critico ? 'paso-critico' : '' }}">
  <td class="paso-num">{{ $paso->orden }}</td>
  <td class="paso-texto">{{ $paso->texto }}</td>
  @if($paso->tiempo_min)
  <td class="paso-tiempo">{{ $paso->tiempo_min }}&nbsp;min</td>
  @else
  <td></td>
  @endif
</tr>
@endforeach
</table>
