<head>
    <style>
        body {
            font-family: Calibri, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000000;
        }

        h1 {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
        }

        ul,
        li {
            font-size: 11px;
            color: #000000;
        }

        a { text-decoration: none; color: #000000; }
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

