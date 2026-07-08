<head>
    <style>
        a { text-decoration: none; color: blue; }
    </style>
</head>
<div>

<h1>Índice de Notas Médicas</h1>

<ul>
@foreach($indiceNotas as $nota)
    <li>
        <a href="#{{ $nota['anchor'] }}">
            {{ $nota['titulo'] }}
        </a>
    </li>
@endforeach
</ul>

</div>

