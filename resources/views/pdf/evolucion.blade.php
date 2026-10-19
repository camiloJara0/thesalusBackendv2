@php
// Decodificar el JSON a un array asociativo
$signos = json_decode($examenFisico->signosVitales, true);
@endphp

<head>
    <meta charset="UTF-8">
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: Calibri, Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #000000;
    }

    .bodyPDF {
        font-family: Calibri, Arial, Helvetica, sans-serif;
        color: #000000;
        font-size: 11px;
        line-height: 1.45;
    }

    @page {
        margin: 220px 35px 60px 35px;
    }

    .pagenum:before {
        content: counter(page);
    }

    /* =======================
   TABLAS
======================= */

    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    }

    th {
        background: #e6e6e6;
        color: #000000;
        font-weight: bold;
    }

    th,
    td {
        border: 1px solid #cccccc;
        padding: 3px;
        vertical-align: top;
        font-size: 11px;
    }

    tr:nth-child(even) {
        background: #f7f7f7;
    }

    /* =======================
   TITULOS
======================= */

    h3 {
        margin: 18px 0 8px;
        padding: 7px 10px;

        color: #000000;

        font-size: 11px;

        border-bottom: none;

        font-weight: bold;

        letter-spacing: .3px;
    }

    /* =======================
   HEADER
======================= */

    header {
        position: fixed;
        top: -200px;
        left: 0;
        right: 0;
        height: 180px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        font-family: Calibri, Arial, Helvetica, sans-serif;
        font-size: 11px;
        border: 1px solid #b3b3b3;
    }

    .header-top {
        background: #d9d9d9;
        color: #000000;
    }

    .header-title {
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .header-subtitle {
        font-size: 11px;
        color: #000000;
    }

    .header-info {
        font-size: 11px;
        color: #000000;
        line-height: 14px;
    }

    .logo-box {
        text-align: center;
        vertical-align: middle;
        background: #ffffff;
        border-right: 1px solid #d8d8d8;
    }

    .logo-box img {
        width: 50px;
    }

    .company-name {
        font-size: 11px;
        font-weight: bold;
        color: #000000;
        margin-top: 3px;
    }

    .document-box {
        background: #f5f5f5;
        border-left: 1px solid #d8d8d8;
        padding-left: 8px;
    }

    .document-box strong {
        color: #000000;
    }

    .header-divider {
        background: #ededed;
        border-top: 1px solid #dddddd;
    }

    /* =======================
   PACIENTE
======================= */

    .label {
        font-weight: bold;
        color: #000000;
    }

    .value {
        color: #000000;
    }

    /* =======================
   NOTA
======================= */

    .noteBox {
        border: 1px solid #cccccc;
        padding: 10px;
        background: #fafafa;
    }

    .noteSection {
        background: #ececec;
        padding: 5px 8px;
        font-weight: bold;
        color: #000000;
        border-left: 4px solid #404040;
        margin-top: 8px;
        margin-bottom: 5px;
    }

    .noteItem {
        border-bottom: 1px solid #eeeeee;
        padding: 4px 0;
    }

    .noteHour {
        font-weight: bold;
        color: #000000;
        width: 55px;
        display: inline-block;
    }

    .noteText {
        color: #000000;
    }

    /* =======================
   DIAGNOSTICOS
======================= */

    .diagHeader {
        background: #f2f2f2;
        color: #000000;
    }

    /* =======================
   FIRMA
======================= */

    .signature {
        margin-top: 35px;
    }

    .signature td {
        height: 90px;
    }

    .signatureName {
        font-weight: bold;
        font-size: 11px;
        color: #000000;
    }

    .signatureDoc {
        font-size: 11px;
        color: #000000;
    }

    /* =======================
   HR
======================= */

    hr {
        border: none;
        border-top: 1px solid #dddddd;
        margin: 6px 0;
    }

    .table-sinBordes tr, td {
        border: none;
    }
    </style>
</head>

<body class="bodyPDF">
    <!-- ENCABEZADO -->

    <header>

        <table class="header-table">

            <tr class="header-top">

                <td width="30%" class="logo-box">

                    @if($convenios && $convenios->logo && Storage::exists($convenios->logo))
                    <img src="{{ public_path('storage/'.$convenios->logo) }}" style="width:60px; height:auto;">
                    @else
                    <img src="{{ public_path('logo.png') }}" style="width:60px; height:auto;">
                    @endif

                    <div class="company-name">
                        {{ $convenios->nombre ?? 'Santa Isabel IPS' }}
                    </div>

                </td>

                <td width="70%" style="padding:6px 6px 0px;text-align:center;">

                    <table class="table-sinBordes">
                        <tr>
                            <td>
                                Historia domiciliaria {{ strtoupper($analisis->nombreServicio) }}
                            </td>
                            <td>
                                Pagina <span class="pagenum"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                {{ $convenios->nombre ?? 'Santa Isabel IPS' }}
                            </td>
                            <td>
                                <strong class="label">Historia clinica:</strong>
                                HC-{{ $analisis->id }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="label">Telefono:</strong> 6024891626-3176689108-3176401540
                            </td>
                            <td>
                                Email: <a class="label" href="mailto:CONTACTO@ICOMSALUDIPS.COM">CONTACTO@ICOMSALUDIPS.COM</a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="label">Identidicacion RC:</strong> {{ $paciente->name }}</td>
                            <td><strong class="label">No. documento:</strong> {{ $paciente->No_document }}</td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="label">Fecha de nacimiento:</strong> {{ $paciente->nacimiento }}
                            </td>
                            <td>
                                <strong class="label">Edad:</strong> {{ \Carbon\Carbon::parse($paciente->nacimiento)->age }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="label">Sexo:</strong> {{ $paciente->sexo }}
                            </td>
                            <td>
                                <strong class="label">Regimen:</strong>
                                {{ $paciente->regimen ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="label">Impreso por:</strong> {{ $paciente->sexo }}
                            </td>
                            <td>
                                <strong class="label">Fecha de impresion:</strong>
                                {{ $paciente->regimen ?? 'N/A' }}
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

    </header>

    <!-- DATOS DEL PACIENTE -->
    <table>

        <tr>
            <td>
                <strong class="label">Direccion:</strong> {{ $paciente->direccion }}
            </td>
            <td>
                <strong class="label">Estado Civil:</strong>
                {{ 'N/A' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong class="label">Ciudad:</strong> {{ $paciente->municipio }}
            </td>
            <td>
                <strong class="label">Barrio:</strong>
                {{ $paciente->barrio ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td><strong class="label">Nombre completo:</strong> {{ $paciente->name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>
                <strong class="label">EPS:</strong> {{ $paciente->Eps }}
            </td>
            <td>
                <strong class="label">Zona:</strong>
                {{ $paciente->zona ?? 'N/A' }}
            </td>
        </tr>

        <tr>
            <td></td>
        </tr>
    </table>

    <div style="margin-bottom: 10px;">
        <h3
            style="font-size: 11px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000000; padding-bottom: 5px;">
            Finalidad y causa externa
        </h3>
        <div style="font-size: 11px;">
            <div style="text-align: justify; padding: 8px;">
                {{ $analisis->motivo }}
            </div>
        </div>
    </div>

    @if($examenFisico->signosVitales)
    <div style="margin-bottom: 10px;">
        <h3
            style="font-size: 11px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000000; padding-bottom: 5px; width">
            Signos vitales
        </h3>
        <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
            <tr>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">TA</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">FC</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">FR</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">T</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">SAT o2</th>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $signos['ta'] ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $signos['fc'] ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $signos['fr'] ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $signos['t'] ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $signos['SATo2'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- DIAGNÓSTICOS -->
    @if($diagnosticos)
    <div style="margin-bottom: 10px;">
        <h3
            style="font-size: 11px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000000; padding-bottom: 5px;">
            Diagnósticos
        </h3>
        <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
            <tr>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">Diagnóstico</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left; width: 15%;">CIE-10</th>
            </tr>
            @forelse($diagnosticos as $diag)
            <tr>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $diag->descripcion }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $diag->codigo }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="padding: 8px; border: 1px solid #cccccc;">Sin diagnósticos registrados</td>
            </tr>
            @endforelse
        </table>
    </div>
    @endif


    <!-- EVOLUCION -->
        <h3
            style="font-size: 11px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000000; padding-bottom: 5px;">
            Evoluciones
        </h3>
    <div style="margin-bottom: 20px; font-size: 11px;">
        <div style="text-align: justify; padding: 8px;">
            {{ $analisis->analisis }}
        </div>
    </div>


    <!-- FIRMA Y SELLO -->
    <table style="margin-top:40px;">
        <tr>
            <td style="text-align:center; border-top:1px solid #000000;">
                <div style="text-align:center; border-top:1px solid #000000;">
                    @if($profesional->sello)
                    <img src="{{ public_path('storage/'.$profesional->sello) }}"
                        style="width:100px; height:100px; object-fit:contain;" />
                    @else
                    <p style="margin-bottom: 50px;"></p>
                    @endif
                </div>
                <p><strong>{{ $profesional->name }}</strong></p>
                <p>{{ $profesional->No_document }}</p>
            </td>
        </tr>
    </table>
</body>