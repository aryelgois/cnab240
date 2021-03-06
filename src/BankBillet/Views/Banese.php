<?php
/**
 * This Software is part of aryelgois/bank-interchange and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\BankInterchange\BankBillet\Views;

use aryelgois\Utils\Validation;
use aryelgois\BankInterchange;

/**
 * Generates bank billets for Banese
 *
 * @author Aryel Mota Góis
 * @license MIT
 * @link https://www.github.com/aryelgois/bank-interchange
 */
class Banese extends BankInterchange\BankBillet\View
{
    const FONTS = [
        'digitable'  => ['Arial', 'B',  8, [ 0,  0,  0]],
        'digitable1' => ['Arial', 'B', 10, [ 0,  0,  0]],
        'billhead'   => ['Arial', '',   6, [ 0,  0,  0]],
        'bank_code'  => ['Arial', 'B',  9, [ 0,  0,  0]],
        'cell_title' => ['Arial', '',   6, [20, 20, 20]],
        'cell_data'  => ['Arial', 'B',  7, [ 0,  0,  0]],
        'footer'     => ['Arial', '',   9, [ 0,  0,  0]]
    ];

    const DASH_STYLE = [0.625, 0.75];

    const DEFAULT_LINE_WIDTH = 0.3;

    /**
     * Procedurally draws the bank billet using FPDF methods
     */
    protected function drawBillet()
    {
        // Add document to assignor
        $assignor = $this->title->assignment->assignor;
        $this->fields['assignor']['value'] .= '     '
            . $assignor->person->getFormattedDocument(true);

        // Draw billet
        $dict = $this->dictionary;

        $this->AddPage();

        $this->drawPageHeader();

        $this->billetSetFont('cell_data');
        $this->drawDash($dict['client_receipt']);

        $this->drawBillhead();

        $this->drawTable('demonstrative');

        $this->Ln(4);

        $this->billetSetFont('cell_title');
        $this->drawDash($dict['compensation']);

        $this->SetY($this->GetY() - 3);

        $this->drawTable('instructions');

        $this->SetY($this->GetY() - 3);
        $this->drawBarCode();
    }

    /**
     * Extends drawGenericTable2()
     *
     * @param string $big_cell @see drawGenericTable2()
     */
    protected function drawTable($big_cell)
    {
        $this->drawBankHeader('L', 1);

        $this->drawGenericTable2(
            $big_cell,
            'LBR',
            [
                127.2, 49.8,
                127.2, 49.8,
                 32  , 27  , 20, 12, 36.2, 49.8,
                 32  , 16  , 11, 32, 36.2, 49.8,
                127.2, 49.8
            ]
        );

        $dict = $this->dictionary;
        $fields = $this->fields;
        $title = $this->title;
        $client = $title->client;
        $wallet = $title->assignment->wallet;

        // Client
        $client_data = $fields['client']['value'] . "\n"
            . utf8_decode($client->address->outputLong());
        $client_doc = $client->person->getFormattedDocument(true);
        $y = $this->GetY();
        $this->billetSetFont('cell_title');
        $this->Cell(10, 3.5, $fields['client']['text']);
        $this->SetXY($this->GetX() + 5, $y);
        $this->billetSetFont('cell_data');
        $this->MultiCell(112.2, 3.5, $client_data);
        $y1 = $this->GetY();
        $this->SetXY(119.2, $y);
        $this->Cell(36, 3.5, $client_doc, 0, 0, 'C');
        $this->setY($y1);

        // Guarantor
        $this->billetSetFont('cell_title');
        $this->Cell(24, 3.5, $fields['guarantor']['text']);
        $this->billetSetFont('cell_data');
        $this->Cell(153, 3.5, $fields['guarantor']['value'], 0, 1);
        $x = $this->GetX();
        $y1 = $this->GetY();

        // Client / Guarantor border
        $this->Line($x, $y, $x, $y1);
        $this->Line($x, $y1, 187, $y1);
        $this->Line(187, $y, 187, $y1);

        // Mechanical authentication
        $text = $dict['mech_auth'] . '/' . utf8_decode($wallet->name);
        $this->SetX(119.2);
        $this->billetSetFont('cell_title');
        $this->Cell(67.8, 3.5, $text);
        $this->Ln(3.5);
    }

    /**
     * Free space: Asbace key
     *
     * Here: Agency . Account . Our number . Bank code . 2 check digits
     */
    protected function generateFreeSpace()
    {
        $assignment = $this->title->assignment;
        $bank = $assignment->bank;

        $key = BankInterchange\Utils::padNumber($assignment->agency, 2, true)
            . BankInterchange\Utils::padNumber($assignment->account, 9, true)
            . $this->formatOurNumber()
            . BankInterchange\Utils::padNumber($bank->code, 3, true);
        $cd1 = Validation::mod10($key);
        $cd2 = Validation::mod11($key . $cd1, 7);

        if ($cd2 === 1) {
            if ($cd1 < 9) {
                $cd1++;
                $cd2 = Validation::mod11($key . $cd1, 7);
            } elseif ($cd1 === 9) {
                $cd1 = 0;
                $cd2 = Validation::mod11($key . $cd1, 7);
            }
        } elseif ($cd2 > 1) {
            $cd2 = 11 - $cd2;
        }

        return $key . $cd1 . $cd2;
    }

    /**
     * Modifies $dictionary before its UTF8 decoding
     */
    protected function updateDictionary()
    {
        // Change some terms
        $this->dictionary = array_replace(
            $this->dictionary,
            [
                'agency_code'   => 'Agência/Cod. Beneficiário',
                'date_process'  => 'Data do processameto',
                'doc_number_sh' => 'Nº do documento',
                'discount'      => '(-) Desconto/ Abatimento',
                'doc_value'     => 'Valor',
                'doc_valueU'    => 'Valor',
                'doc_value='    => '(=) Valor do documento',
                'fine'          => '(+) Mora/Multa',
                'guarantor'     => 'Sacador/Avalista: ',
                'instructions'  => 'Instruções',
                'kind'          => 'Espécie doc',
                'currency'      => 'Moeda',
            ]
        );

        // Make most terms upper case
        $keys = [
            'accept', 'addition', 'agency_code', 'amount', 'assignor',
            'bank_use', 'charged', 'client', 'currency', 'date_document',
            'date_due', 'date_process', 'deduction', 'demonstrative',
            'discount', 'doc_number_sh', 'doc_value', 'doc_value=',
            'doc_valueU', 'fine', 'guarantor', 'instructions', 'kind',
            'mech_auth', 'our_number', 'payment_place', 'wallet',
        ];
        foreach ($keys as $key) {
            $this->dictionary[$key] = mb_strtoupper($this->dictionary[$key]);
        }
    }
}
