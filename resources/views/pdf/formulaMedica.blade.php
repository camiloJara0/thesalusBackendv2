
<head>
    <meta charset="UTF-8">
    <title>FORMULA MEDICA</title>
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: Calibri, Arial, Helvetica, sans-serif;
        font-size: 14px;
        color: #000000;
    }

    .bodyPDF {
        font-family: Calibri, Arial, Helvetica, sans-serif;
        color: #000000;
        font-size: 14px;
        line-height: 1.45;
    }

    @page {
        margin: 100px 80px 80px 35px;
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
        font-size: 14px;
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
        font-size: 18px;
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
    <p style="color: #000000; text-align: right; width: 100%;"><span class="pagenum"></span></p>
    <div>
        <div style="margin: 0 auto; text-align: center; width: 100%; ">
            @if($convenios && $convenios->logo && Storage::exists($convenios->logo))
            <img src="{{ public_path('storage/'.$convenios->logo) }}" style="width:180px; height:auto;">
            @else
            <img src="{{ public_path('logo.png') }}" style="width:180px; height:auto;">
            @endif

            <div class="company-name">
                FORMULA MEDICA
            </div>
        </div>
    </div>

    <!-- DATOS DEL PACIENTE -->
    <table style="margin: 30px 160px 30px 120px;">
        <tr>
            <td><strong>Fecha</strong> {{ \Carbon\Carbon::parse($analisis->created_at)->format('Y/m/d') ?? now()->format('Y-m-d') }}</td>
            <td></td>
        </tr>
        <tr>
            <td><strong class="label"> Usuario (a): </strong> {{ $paciente->name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>
                <strong class="label">I.D. </strong> {{ $paciente->No_document }}<br />
            </td>
            <td>
                <strong class="label">Edad: </strong> {{ \Carbon\Carbon::parse($paciente->nacimiento)->age }}<br />
            </td>
        </tr>
    </table>

    <!-- Diagnosticos -->
    <p style="margin: 0px 100px 30px 100px; width: 100%;">
        @foreach($diagnosticos as $diagnostico)
        <span>
            {{ $diagnostico->descripcion }},
        </span>
        @endforeach
    </p>

    <!-- Medicamentos -->
    <div style="margin: 0px 120px 30px 120px; width: 100%;">
        @foreach($medicamentos as $medicamento)
        <p> - 
            <span >{{ $medicamento->medicamento }},</span>
            <span >Dosis: {{ $medicamento->dosis }},</span>
            <span >Cantidad: {{ $medicamento->cantidad }}</span>
        </p>
        @endforeach

    </div>

    <p style="margin:60px 0px 0px 100px;">Fórmula válida por 90 dÌas</p>
    <!-- FIRMA Y SELLO -->

    <div style="margin-left: 20px;">
        @if($profesional->sello)
        <img src="{{ public_path('storage/'.$profesional->sello) }}"
            style="width:100px; height:100px; object-fit:contain;" />
        @else
        <p>Firma y Sello:</p>
        @endif
    </div>
</div>