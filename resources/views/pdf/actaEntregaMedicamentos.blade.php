<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>ACTA DE ENTREGA MEDICAMENTOS</title>
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

<body>
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
                        Entrega de medicamentos
                    </div>

                    <div class="header-subtitle">
                        Insumos medicos y de salud
                    </div>

                </td>

                <td width="25%" rowspan="2" class="document-box">

                    <table class="table-sinBordes" style="width:100%;font-size:8px;border-collapse:collapse;">

                        <tr>
                            <td><strong>Versión</strong></td>
                            <td style="color: #000;">1.0</td>
                        </tr>

                        <tr>
                            <td><strong>Fecha</strong></td>
                            <td style="color: #000;">{{ $medicamentos[0]->created_at->format('Y-m-d') }}</td>
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

                            <td width="50%">
                                <strong>Proceso:</strong>
                                Entrega de insumos
                            </td>

                            <td width="50%">
                                <strong>Documento:</strong>
                                Acta de entrega
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>
        </table>

    </header>
    <p style="font-size: 11px;">El(La) señor(a) {{ $paciente->name }}, identificado(a) con {{ $paciente->type_doc }} No: {{ $paciente->No_document }},
    Solicita al Servicio Farmacéutico de SALUDCOM entregar el medicamento correspondiente, con fecha {{ $medicamentos[0]->created_at->format('Y-m-d') }}</p>
    <!-- DATOS DEL PACIENTE -->
    <h3>NOTA</h3>
    <p style="font-size: 11px;">Para llevar el control de las entregas cada vez que se entregue se deberá diligenciar el siguiente cuadro.</p>

    <!-- DIAGNÓSTICOS -->
    <div style="margin-bottom: 20px;">
        <h3
            style="font-size: 13px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">
            FÓRMULA MEDICA
        </h3>
        <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
            <tr class="diagHeader">
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Fecha</th>
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nombre del medicamento</th>
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left; width: 15%;">Observacion</th>
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left; width: 15%;">Cantidad</th>
            </tr>
            @foreach($medicamentos as $medicamento)
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $medicamento->created_at->format('Y-m-d') }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $medicamento->medicamento }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $medicamento->observacion }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $medicamento->cantidad }}</td>
            </tr>
            @endforeach
        </table>
    </div>

    <!-- FIRMA Y SELLO -->
    <table style="margin-top:40px;">
        <tr>
            <td style="text-align:center;">
                <p style="margin-top: 70px; "></p>
                <p style="border-top: 1px solid #000;">Firma y Cedula de quien Recibe</p>
            </td>
            <td style="text-align:center;">
                @if(!empty($profesional?->sello))
                <img src="{{ public_path('storage/'.$profesional->sello) }}"
                    style="width:70px; height:70px; object-fit:contain;" />
                @else
                <p style="margin-top: 70px; "></p>
                @endif
                <p style="border-top: 1px solid #000;">Firma y Cedula de quien Entrega</p>
            </td>
        </tr>
    </table>
</body>

</html>