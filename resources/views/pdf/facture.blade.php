<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero }}</title>

    <style>
        @page {
            margin: 35px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #263238;
            line-height: 1.5;
        }

        table {
            width: 100%;
        }

        .header {
            margin-bottom: 30px;
        }

        .header td {
            width: 50%;
            vertical-align: top;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #123b63;
        }

        .document-title {
            font-size: 27px;
            font-weight: bold;
            color: #123b63;
            text-align: right;
        }

        .document-number {
            text-align: right;
        }

        .section-title {
            color: #123b63;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .details {
            margin-bottom: 25px;
        }

        .details td {
            width: 50%;
            vertical-align: top;
            padding: 12px;
            background-color: #f3f6f9;
        }

        .items {
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items th {
            background-color: #123b63;
            color: white;
            text-align: left;
            padding: 10px 7px;
        }

        .items td {
            border-bottom: 1px solid #dce3e8;
            padding: 10px 7px;
        }

        .right {
            text-align: right;
        }

        .totals {
            width: 48%;
            margin-left: 52%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .totals td {
            padding: 7px;
            border-bottom: 1px solid #dce3e8;
        }

        .total-ttc td {
            font-size: 14px;
            font-weight: bold;
            color: #123b63;
            border-top: 2px solid #123b63;
        }

        .notes {
            margin-top: 30px;
            padding: 12px;
            background-color: #f3f6f9;
        }

        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #dce3e8;
            text-align: center;
            color: #607080;
            font-size: 9px;
        }
    </style>
</head>

<body>

<table class="header">
    <tr>
        <td>
            <div class="company-name">
                {{ $entreprise->nom }}
            </div>

            @if($entreprise->adresse)
                {{ $entreprise->adresse }}<br>
            @endif

            @if($entreprise->code_postal || $entreprise->ville)
                {{ $entreprise->code_postal }} {{ $entreprise->ville }}<br>
            @endif

            @if($entreprise->telephone)
                Téléphone : {{ $entreprise->telephone }}<br>
            @endif

            @if($entreprise->email)
                Email : {{ $entreprise->email }}<br>
            @endif

            @if($entreprise->siret)
                SIRET : {{ $entreprise->siret }}<br>
            @endif

            @if($entreprise->numero_tva)
                TVA intracommunautaire : {{ $entreprise->numero_tva }}
            @endif
        </td>

        <td>
            <div class="document-title">FACTURE</div>

            <div class="document-number">
                <strong>N° {{ $facture->numero }}</strong><br>

                Date :
                {{ $facture->date_emission
                    ? $facture->date_emission->format('d/m/Y')
                    : 'Non renseignée' }}
                <br>

                @if($facture->date_echeance)
                    Échéance :
                    {{ $facture->date_echeance->format('d/m/Y') }}
                    <br>
                @endif

                @php
                    $statutAffiche = $facture->statut;

                    if (
                        $facture->statut === 'envoyee'
                        && $facture->date_echeance
                        && $facture->date_echeance->lt(today())
                    ) {
                        $statutAffiche = 'en_retard';
                    }

                    $libellesStatuts = [
                        'brouillon' => 'Brouillon',
                        'envoyee' => 'Envoyée',
                        'payee' => 'Payée',
                        'annulee' => 'Annulée',
                        'en_retard' => 'En retard',
                    ];
                @endphp

                Statut :
                {{ $libellesStatuts[$statutAffiche] ?? ucfirst($statutAffiche) }}
            </div>
        </td>
    </tr>
</table>

<table class="details">
    <tr>
        <td>
            <div class="section-title">FACTURER À</div>

            <strong>
                {{ trim(($client->prenom ?? '') . ' ' . ($client->nom ?? '')) }}
            </strong><br>

            @if($client->adresse)
                {{ $client->adresse }}<br>
            @endif

            @if($client->code_postal || $client->ville)
                {{ $client->code_postal }} {{ $client->ville }}<br>
            @endif

            @if($client->email)
                Email : {{ $client->email }}<br>
            @endif

            @if($client->telephone)
                Téléphone : {{ $client->telephone }}
            @endif
        </td>

        <td>
            <div class="section-title">INFORMATIONS</div>

            Numéro de facture :
            <strong>{{ $facture->numero }}</strong><br>

            @if($facture->devis)
                Devis associé : {{ $facture->devis->numero }}<br>
            @endif

            Devise : Euro (€)
        </td>
    </tr>
</table>

<div class="section-title">DÉTAILS DE LA FACTURE</div>

<table class="items">
    <thead>
        <tr>
            <th>Désignation</th>
            <th class="right">Montant HT</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>
                Prestations de travaux électriques

                @if($facture->devis)
                    <br>
                    Selon le devis {{ $facture->devis->numero }}
                @endif
            </td>

            <td class="right">
                {{ number_format((float) $facture->montant_ht, 2, ',', ' ') }} €
            </td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Total HT</td>
        <td class="right">
            {{ number_format((float) $facture->montant_ht, 2, ',', ' ') }} €
        </td>
    </tr>

    <tr>
        <td>
            TVA ({{ number_format((float) $facture->taux_tva, 2, ',', ' ') }} %)
        </td>

        <td class="right">
            {{ number_format((float) $facture->montant_tva, 2, ',', ' ') }} €
        </td>
    </tr>

    <tr class="total-ttc">
        <td>Total TTC</td>
        <td class="right">
            {{ number_format((float) $facture->montant_ttc, 2, ',', ' ') }} €
        </td>
    </tr>
</table>

@if($facture->notes)
    <div class="notes">
        <div class="section-title">NOTES</div>
        {{ $facture->notes }}
    </div>
@endif

<div class="footer">
    {{ $entreprise->nom }} — Facture {{ $facture->numero }} —
    Document généré automatiquement par ProElec.
</div>

</body>
</html>