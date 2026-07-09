<head>
    <meta charset="UTF-8">
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
    }

    .bodyPDF {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        color: #2f3542;
        font-size: 10px;
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
        background: #eef4fb;
        color: #24476b;
        font-weight: bold;
    }

    th,
    td {
        border: 1px solid #d8e0ea;
        padding: 7px;
        vertical-align: top;
        font-size: 10px;
    }

    tr:nth-child(even) {
        background: #fafbfd;
    }

    /* =======================
   TITULOS
======================= */

    h3 {
        margin: 18px 0 8px;
        padding: 7px 10px;

        background: #edf5ff;

        color: #205493;

        font-size: 12px;

        border-left: 5px solid #2f80ed;

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
        font-family: DejaVu Sans, Arial, sans-serif;
        border: 1px solid #BFC9D4;
    }

    .header-top {
        background: #1E5D8C;
        color: #FFF;
    }

    .header-title {
        font-size: 15px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .header-subtitle {
        font-size: 10px;
        color: #EAF3FA;
    }

    .header-info {
        font-size: 9px;
        color: #444;
        line-height: 14px;
    }

    .logo-box {
        text-align: center;
        vertical-align: middle;
        background: #FFF;
        border-right: 1px solid #D8D8D8;
    }

    .logo-box img {
        width: 50px;
    }

    .company-name {
        font-size: 10px;
        font-weight: bold;
        color: #1E5D8C;
        margin-top: 3px;
    }

    .document-box {
        background: #F5F8FB;
        border-left: 1px solid #D8D8D8;
        padding-left: 8px;
    }

    .document-box strong {
        color: #1E5D8C;
    }

    .header-divider {
        background: #EDF2F7;
        border-top: 1px solid #D7DFE8;
    }

    /* =======================
   PACIENTE
======================= */

    .label {
        font-weight: bold;
        color: #4a5568;
    }

    .value {
        color: #1f2937;
    }

    /* =======================
   NOTA
======================= */

    .noteBox {
        border: 1px solid #d9e2ec;
        padding: 10px;
        background: #fcfdff;
    }

    .noteSection {
        background: #f5f9ff;
        padding: 5px 8px;
        font-weight: bold;
        color: #245b9a;
        border-left: 4px solid #2f80ed;
        margin-top: 8px;
        margin-bottom: 5px;
    }

    .noteItem {
        border-bottom: 1px solid #eceff3;
        padding: 4px 0;
    }

    .noteHour {
        font-weight: bold;
        color: #245b9a;
        width: 55px;
        display: inline-block;
    }

    .noteText {
        color: #374151;
    }

    /* =======================
   DIAGNOSTICOS
======================= */

    .diagHeader {
        background: #edf5ff;
        color: #245b9a;
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
        color: #1f2937;
    }

    .signatureDoc {
        font-size: 9px;
        color: #6b7280;
    }

    /* =======================
   HR
======================= */

    hr {
        border: none;
        border-top: 1px solid #dbe3ec;
        margin: 6px 0;
    }

    .table-sinBordes tr, td {
        border: none;
    }
    </style>
</head>

<div class="bodyPDF">
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
                        HISTORIA CLÍNICA
                    </div>

                    <div class="header-subtitle">
                        {{ strtoupper($analisis->servicio->name) }}
                    </div>

                </td>

                <td width="25%" rowspan="2" class="document-box">

                    <table class="table-sinBordes" style="width:100%;font-size:8px;border-collapse:collapse;">

                        <tr>
                            <td><strong>Código</strong></td>
                            <td style="color: #000;">HC-{{ $analisis->id }}</td>
                        </tr>

                        <tr>
                            <td><strong>Versión</strong></td>
                            <td style="color: #000;">1.0</td>
                        </tr>

                        <tr>
                            <td><strong>Fecha</strong></td>
                            <td style="color: #000;">{{ \Carbon\Carbon::parse($analisis->created_at)->format('Y/m/d') ?? now()->format('Y-m-d') }}</td>
                        </tr>

                        <tr>
                            <td><strong>Página</strong></td>
                            <td style="color: #000;"><span class="pagenum"></span></td>
                        </tr>

                    </table>

                </td>

            </tr>

            <tr class="header-divider">

                <td style="padding:4px 10px;">

                    <table class="table-sinBordes" style="width:100%;border-collapse:collapse;font-size:9px;">

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
                                Trabajo Social
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

    <!-- DIAGNÓSTICOS -->
    <div style="margin-bottom: 20px;">
        <h3
            style="font-size: 13px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">
            DIAGNÓSTICOS
        </h3>
        <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
            <tr class="diagHeader">
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Diagnóstico</th>
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left; width: 15%;">CIE-10</th>
            </tr>
            @forelse($diagnosticos as $diag)
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $diag->descripcion }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $diag->codigo }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="padding: 8px; border: 1px solid #ddd;">Sin diagnósticos registrados</td>
            </tr>
            @endforelse
        </table>
    </div>

    <div style="margin-bottom: 20px;">
        <h3
            style="font-size: 13px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">
            TRABAJO SOCIAL
        </h3>
        <div style="margin-bottom: 20px; font-size:10px;">
            <h3 class="diagHeader" style="padding: 8px; border: 1px solid #ddd; text-align: center;">Motivo de
                consulta</h3>
            <div style="text-align: justify; padding: 10px; border: 1px solid #ddd;">
                {{ $analisis->motivo }}
            </div>
        </div>
    </div>

    <!-- EVOLUCION -->
    <div style="margin-bottom: 20px;">
        <div style="margin-bottom: 20px; font-size:10px;">
            <h3 class="diagHeader" style="padding: 8px; border: 1px solid #ddd; text-align: center;">ANÁLISIS /
                TRATAMIENTOS</h3>
            <div style="text-align: justify; padding: 10px; border: 1px solid #ddd;">
                {{ $analisis->analisis }}
            </div>
        </div>
    </div>


    <!-- FIRMA Y SELLO -->
    <table style="margin-top:40px;">
        <tr>
            <td style="text-align:center; border-top:1px solid #000;">
                <p><strong>{{ $profesional->name }}</strong></p>
                <p>{{ $profesional->No_document }}</p>
            </td>
            <td style="text-align:center; border-top:1px solid #000;">
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