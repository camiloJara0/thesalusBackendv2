<head>
    <meta charset="UTF-8">
    <title>PROCEDIMIENTOS ASIGNADOS</title>
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
        margin: 190px 35px 60px 35px;
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
        padding: 7px;
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

        background: #f2f2f2;

        color: #000000;

        font-size: 11px;

        border-left: 5px solid #404040;

        border-bottom: none;

        font-weight: bold;

        letter-spacing: .3px;
    }

    /* =======================
   HEADER
======================= */

    header {
        position: fixed;
        top: -170px;
        left: 0;
        right: 0;
        height: 150px;
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

<div>
    <!-- ENCABEZADO -->
    <header>

        <table class="header-table">

            <tr class="header-top">

                <td width="22%" rowspan="2" class="logo-box">

                    @if($convenios && $convenios->logo && Storage::exists($convenios->logo))
                    <img src="{{ public_path('storage/'.$convenios->logo) }}" style="width:60px; height:auto;">
                    @else
                    <img src="{{ public_path('logo.png') }}" style="width:60px; height:auto;">
                    @endif

                    <div class="company-name">
                        {{ $convenios->nombre ?? 'Santa Isabel IPS' }}
                    </div>

                </td>

                <td width="53%" style="padding:6px 6px 0px;text-align:center;">

                    <div class="header-title">
                        PLAN DE MANEJO
                    </div>

                    <div class="header-subtitle">
                        {{ strtoupper($analisis->servicio->name) }}
                    </div>

                </td>

                <td width="25%" rowspan="2" class="document-box">

                    <table class="table-sinBordes" style="width:100%;font-size: 11px;border-collapse:collapse;">

                        <tr>
                            <td><strong>Código</strong></td>
                            <td style="color: #000000;">HC-{{ $analisis->id }}</td>
                        </tr>

                        <tr>
                            <td><strong>Versión</strong></td>
                            <td style="color: #000000;">1.0</td>
                        </tr>

                        <tr>
                            <td><strong>Fecha</strong></td>
                            <td style="color: #000000;">{{ \Carbon\Carbon::parse($analisis->created_at)->format('Y/m/d') ?? now()->format('Y-m-d') }}</td>
                        </tr>

                        <tr>
                            <td><strong>Página</strong></td>
                            <td style="color: #000000;"><span class="pagenum"></span></td>
                        </tr>

                    </table>

                </td>

            </tr>

            <tr class="header-divider">

                <td style="padding:4px 10px;">

                    <table class="table-sinBordes" style="width:100%;border-collapse:collapse;font-size: 11px;">

                        <tr>

                            <td width="35%">
                                <strong>Proceso:</strong>
                                Atención Domiciliaria
                            </td>

                            <td width="35%">
                                <strong>Documento:</strong>
                                Registro Clínico
                            </td>

                            <td width="30%">
                                <strong>Tipo:</strong>
                                Procedimiento
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>
        </table>

    </header>

    <!-- DATOS DEL PACIENTE -->
    <h3>DATOS DEL PACIENTE</h3>
    <table>
        <tr>
            <td><strong class="label">Nombre completo:</strong> {{ $paciente->name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>
                <strong class="label">No. documento:</strong> {{ $paciente->No_document }}<br />
                <strong class="label">Tipo de documento:</strong> {{ $paciente->type_doc }}
            </td>
            <td>
                <strong class="label">Edad:</strong> {{ \Carbon\Carbon::parse($paciente->nacimiento)->age }}<br />
                <strong class="label">Sexo:</strong> {{ $paciente->sexo }}
            </td>
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
    </table>

    <!-- PROCEDIMIENTOS -->
    <div style="margin-bottom: 20px;">
        <h3
            style="font-size: 11px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000000; padding-bottom: 5px; text-transform: uppercase;">
            PROCEDIMIENTOS
        </h3>
        <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
            <tr class="diagHeader">
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">Decripcion</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">CUPS</th>
                <th style="padding: 8px; border: 1px solid #cccccc; text-align: left;">Dias asignados</th>
            </tr>
            @forelse($procedimientos as $procedimiento)
            <tr>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $procedimiento->procedimiento }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $procedimiento->codigo }}</td>
                <td style="padding: 8px; border: 1px solid #cccccc;">{{ $procedimiento->dias_asignados }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="padding: 8px; border: 1px solid #cccccc;">Sin procedimientos registrados</td>
            </tr>
            @endforelse
        </table>
    </div>

    <!-- FIRMA Y SELLO -->
    <table style="margin-top:40px;">
        <tr>
            <td style="text-align:center; border-top:1px solid #000000;">
                <p><strong>{{ $profesional->name }}</strong></p>
                <p>{{ $profesional->No_document }}</p>
            </td>
            <td style="text-align:center; border-top:1px solid #000000;">
                @if($profesional->sello)
                <img src="{{ public_path('storage/'.$profesional->sello) }}"
                    style="width:100px; height:100px; object-fit:contain;" />
                @else
                <p>Firma y Sello</p>
                @endif
            </td>
        </tr>
    </table>
</div>