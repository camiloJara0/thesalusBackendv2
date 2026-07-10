    <header>

        <table class="header-table">

            <tr class="header-top">

                <td width="22%" rowspan="2" class="logo-box">


                    <div class="company-name">
                        {{ $nombre }}
                    </div>

                </td>

                <td width="53%" style="padding:6px 6px 0px;text-align:center;">

                    <div class="header-title">
                        HISTORIA CLÍNICA
                    </div>

                    <div class="header-subtitle">
                        {{ $servicio }}
                    </div>

                </td>

                <td width="25%" rowspan="2" class="document-box">

                    <table class="table-sinBordes" style="width:100%;font-size:8px;border-collapse:collapse;">

                        <tr>
                            <td><strong>Código</strong></td>
                            <td style="color: #000;">HC-{{ $id }}</td>
                        </tr>

                        <tr>
                            <td><strong>Versión</strong></td>
                            <td style="color: #000;">1.0</td>
                        </tr>

                        <tr>
                            <td><strong>Fecha</strong></td>
                            <td style="color: #000;">{{ $fecha }}</td>
                        </tr>

                        <tr>
                            <td><strong>Página</strong></td>
                            <td style="color: #000;"><span>{ PAGENO }</span></td>
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
                                Evolución
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>
        </table>

    </header>