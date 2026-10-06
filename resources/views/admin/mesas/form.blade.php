<div class="campo">
    <label for="numero">Número de mesa</label>
    <input id="numero" name="numero" type="number" min="1" max="2147483647" value="{{ old('numero', $mesa?->numero) }}" required autofocus>
</div>
<div class="campo">
    <label for="estado">Estado</label>
    <select id="estado" name="estado" required>
        @foreach($estados as $valor => $texto)
            <option value="{{ $valor }}" @selected(old('estado', $mesa?->estado ?? 'libre') === $valor)>{{ $texto }}</option>
        @endforeach
    </select>
</div>
<div class="campo">
    <label for="mozo_id">Mozo asignado</label>
    <select id="mozo_id" name="mozo_id">
        <option value="">Sin asignar</option>
        @if($mesa?->mozo && !$mozos->contains('id', $mesa->mozo_id))
            <option value="{{ $mesa->mozo_id }}" @selected((string) old('mozo_id', $mesa->mozo_id) === (string) $mesa->mozo_id)>{{ $mesa->mozo->name }} (asignación anterior)</option>
        @endif
        @foreach($mozos as $mozo)
            <option value="{{ $mozo->id }}" @selected((string) old('mozo_id', $mesa?->mozo_id) === (string) $mozo->id)>{{ $mozo->name }}</option>
        @endforeach
    </select>
    @if($mozos->isEmpty())<p class="ayuda">No hay mozos activos disponibles. Podés guardar la mesa sin asignar.</p>@endif
</div>
<div class="campo">
    <label for="cantidad_personas">Cantidad de personas</label>
    <input id="cantidad_personas" name="cantidad_personas" type="number" min="0" max="2147483647" value="{{ old('cantidad_personas', $mesa?->cantidad_personas ?? 0) }}" required>
</div>
<div class="acciones"><a href="{{ route('admin.mesas.index') }}">Cancelar</a><button class="primario">Guardar</button></div>
