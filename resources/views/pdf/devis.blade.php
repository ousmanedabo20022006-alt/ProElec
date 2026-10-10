<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <title>Devis {{ $devis->numero }}</title>

    <style>
        @page {
            margin: 30px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #172033;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #dbe2ea;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 45px;
            height: 45px;
            background: #155eef;
            color: white;
            text-align: center;
            vertical-align: middle;
            font-size: 22px;
            font-weight: bold;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-left: 10px;
        }

        .company-subtitle {
            color: #64748b;
            font-size: 10px;
        }

        .document-title {
            text-align: right;
        }

        .document-title-label {
            color: #155eef;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .document-number {
            font-size: 20px;
            font-weight: bold;
            margin-top: 4px;
        }

        .document-date {
            color: #64748b;
            font-size: 10px;
        }

        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .section {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .section-title {
            color: #94a3b8;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .bold {
            font-weight: bold;
        }

        .muted {
            color: #64748b;
        }

        .intervention {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            margin-bottom: 25px;
        }

        .intervention-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .details-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .details-table th {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: left;
            font-size: 10px;
        }

        .details-table td {
            border: 1px solid #e2e8f0;
            padding: 12px 10px;
            font-size: 10px;
        }

        .text-right {
            text-align: right !important;
        }

        .totals-container {
            width: 100%;
        }

        .totals {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .total-ttc td {
            background: #172033;
            color: white;
            font-weight: bold;
            font-size: 13px;
            border-bottom: none;
        }

        .notes {
            margin-top: 25px;
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .notes-title {
            color: #94a3b8;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .status {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .status-label {
            color: #94a3b8;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-value {
            font-weight: bold;
            margin-top: 3px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
        }
    </style>
</head>

<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <table>
                        <tr>
                            <td class="logo">P</td>
                            <td style="padding-left: 10px;">
                                <div class="company-name">
                                    ProElec
                                </div>

                                <div class="company-subtitle">
                                    Gestion pour électriciens
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="document-title" style="width: 45%;">
                    <div class="document-title-label">
                        Devis
                    </div>

                    <div class="document-number">
                        {{ $devis->numero }}
                    </div>

                    <div class="document-date">
                        Émis le {{ $devis->date_emission?->format('d/m/Y') }}
                    </div>

                    @if($devis->date_validite)
                        <div class="document-date">
                            Valide jusqu'au
                            {{ $devis->date_validite->format('d/m/Y') }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="section-table">
        <tr>
            <td class="section">
                <div class="section-title">
                    Émetteur
                </div>

                <div class="bold">
                    ProElec
                </div>

                <div class="muted">
                    Gestion pour électriciens
                </div>
            </td>

            <td class="section">
                <div class="section-title">
                    Client
                </div>

                @if($devis->client)
                    <div class="bold">
                        {{ $devis->client->prenom }}
                        {{ $devis->client->nom }}
                    </div>

                    @if($devis->client->email)
                        <div class="muted">
                            {{ $devis->client->email }}
                        </div>
                    @endif

                    @if($devis->client->telephone)
                        <div class="muted">
                            {{ $devis->client->telephone }}
                        </div>
                    @endif

                    @if($devis->client->adresse)
                        <div class="muted">
                            {{ $devis->client->adresse }}
                        </div>
                    @endif

                    @if($devis->client->code_postal || $devis->client->ville)
                        <div class="muted">
                            {{ $devis->client->code_postal }}
                            {{ $devis->client->ville }}
                        </div>
                    @endif
                @else
                    <div class="muted">
                        Client non renseigné
                    </div>
                @endif
            </td>
        </tr>
    </table>

    @if($devis->intervention)
        <div class="intervention">
            <div class="section-title">
                Intervention
            </div>

            <div class="intervention-title">
                {{ $devis->intervention->titre }}
            </div>

            @if($devis->intervention->description)
                <div class="muted">
                    {{ $devis->intervention->description }}
                </div>
            @endif

            @if(
                $devis->intervention->adresse ||
                $devis->intervention->code_postal ||
                $devis->intervention->ville
            )
                <div class="muted" style="margin-top: 6px;">
                    {{ $devis->intervention->adresse }}

                    @if($devis->intervention->code_postal)
                        , {{ $devis->intervention->code_postal }}
                    @endif

                    {{ $devis->intervention->ville }}
                </div>
            @endif
        </div>
    @endif

    <div class="details-title">
        Détail du devis
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>
                    Désignation
                </th>

                <th class="text-right">
                    Montant HT
                </th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    Prestations et travaux électriques
                </td>

                <td class="text-right bold">
                    {{ number_format((float) $devis->montant_ht, 2, ',', ' ') }}
                    €
                </td>
            </tr>
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals">
            <tr>
                <td class="muted">
                    Total HT
                </td>

                <td class="text-right bold">
                    {{ number_format((float) $devis->montant_ht, 2, ',', ' ') }}
                    €
                </td>
            </tr>

            <tr>
                <td class="muted">
                    TVA ({{ number_format((float) $devis->taux_tva, 2, ',', ' ') }} %)
                </td>

                <td class="text-right bold">
                    {{ number_format((float) $devis->montant_tva, 2, ',', ' ') }}
                    €
                </td>
            </tr>

            <tr class="total-ttc">
                <td>
                    Total TTC
                </td>

                <td class="text-right">
                    {{ number_format((float) $devis->montant_ttc, 2, ',', ' ') }}
                    €
                </td>
            </tr>
        </table>
    </div>

    @if($devis->notes)
        <div class="notes">
            <div class="notes-title">
                Notes et conditions
            </div>

            <div>
                {!! nl2br(e($devis->notes)) !!}
            </div>
        </div>
    @endif

    <div class="status">
        <div class="status-label">
            Statut du devis
        </div>

        <div class="status-value">
            @switch($devis->statut)
                @case('brouillon')
                    Brouillon
                    @break

                @case('envoye')
                    Envoyé
                    @break

                @case('accepte')
                    Accepté
                    @break

                @case('refuse')
                    Refusé
                    @break

                @case('expire')
                    Expiré
                    @break

                @default
                    {{ $devis->statut }}
            @endswitch
        </div>
    </div>

    <div class="footer">
        Document généré depuis ProElec
    </div>

</body>
</html>