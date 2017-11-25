<?php

require_once 'autoload.php';

use aryelgois\BankInterchange;
use aryelgois\Medools;

/*
 * helper functions
 */

function protected_example(callable $callback)
{
    try {
        $callback();
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Unknown database') === false) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
        /*
         * Silently skip error:
         * User might not have configured config/medools.php yet
         */
    }
}

function format_model_pretty($model, $html = true)
{
    $person = $model->person;
    $info = ($model instanceof BankInterchange\Models\Assignor)
          ? 'Account: ' . $model->formatAgencyAccount(4, 11)
          : $person->documentFormat(true);

    $result = $person->name
            . ($html ? '<br/><small>' : ' (')
            . $info
            . ($html ? '</small>' : ')');

    return $result;
}

function select_option_foreign_person(Medools\ModelIterator $iterator)
{
    foreach ($iterator as $model) {
        printf(
            "                        <option value=\"%s\">%s</option>\n",
            $model->id,
            format_model_pretty($model, false)
        );
    }
}

/*
 * example functions
 */

function list_payers()
{
    select_option_foreign_person(
        new Medools\ModelIterator('aryelgois\BankInterchange\Models\Payer', [])
    );
}

function list_assignors()
{
    select_option_foreign_person(
        new Medools\ModelIterator('aryelgois\BankInterchange\Models\Assignor', [])
    );
}

function list_titles()
{
    $template = "                <tr>
                    <td><input name=\"titles[]\" value=\"%s\" type=\"checkbox\" /></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td><a href=\"generate_billet.php?id=%s\">pdf</a></td>
                </tr>\n";

    $iterator = new Medools\ModelIterator('aryelgois\BankInterchange\Models\Title', []);
    foreach ($iterator as $model) {
        $id = $model->id;
        $payer = $model->payer;
        $assignor = $model->assignor;
        $value = $model->specie->format($model->value);

        $data = [
            $id,
            $id,
            format_model_pretty($payer),
            format_model_pretty($assignor),
            $value,
            $model->stamp,
            $id,
        ];

        printf($template, ...$data);
    }
}

function list_shipping_files()
{
    $template = "            <tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>
                    <a href=\"generate_cnab.php?cnab=240&id=%s\">CNAB240</a>
                    <a href=\"generate_cnab.php?cnab=400&id=%s\">CNAB400</a>
                </td>
            </tr>\n";

    $shipping_files = new Medools\ModelIterator('aryelgois\BankInterchange\Models\ShippingFile', []);
    foreach ($shipping_files as $shipping_file) {
        $id = $shipping_file->id;
        $titles = [];
        $total = 0.0;

        $shipping_file_titles = new Medools\ModelIterator(
            'aryelgois\BankInterchange\Models\ShippingFileTitle',
            ['shipping_file' => $id]
        );
        foreach ($shipping_file_titles as $sft) {
            $title = $sft->title;
            $titles[] = $title->id;
            $total += (float) $title->value;
        }

        $data = [
            $id,
            implode(', ', $titles),
            $title->specie->format($total),
            $shipping_file->stamp,
            $id,
            $id,
        ];

        printf($template, ...$data);
    }
}

?>
<!doctype html>
<html>
<head>
    <title>Example - BankInterchange</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta charset="UTF-8" />
    <!--[if lt IE 9]><script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script><![endif]-->
    <script>
function select_all(source, name) {
    checkboxes = document.getElementsByName(name);
    for (let i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
    }
}
function element_enabled(id, enabled) {
    document.getElementById(id).disabled = !enabled;
}
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css" />
    <link rel="stylesheet" href="main.css" />
</head>
<body>
    <aside>
        <header>
            <h2>BankInterchange</h2>
            <em>example</em>
        </header>

        <label for="aside-menu">Menu</label>
        <input id="aside-menu" type="checkbox" />
        <nav>
            <a href="#intro">Intro</a>
            <a href="#setup">Setup</a>
            <a href="#new_person">New Person</a>
            <a href="#generate_title">Generate Title</a>
            <a href="#generate_shipping_file">Generate Shipping File</a>
            <a href="#generate_cnab">Generate Cnab</a>
            <a href="#process_return_file">Return File</a>
        </nav>
    </aside>

    <main>
        <section id="intro">
            <h2>Intro</h2>
            <p>
                BankInterchange is suitable to use in a e-Commerce. You just
                need to adapt your website a little bit and provide some
                operations for who is going to use it.
            </p>
            <p>
                Explore this example source code to see how these basic
                operations are implemented. Of course, you will want some data
                validation to protect your customers.
            </p>
        </section>

        <section id="setup">
            <h2>Setup</h2>
            <p>
                In order to use BankInterchange, you need the Database schema it
                uses.
            </p>
            <ol>
                <li>
                    First, you need the Address Database provided
                    <a href="https://github.com/aryelgois/databases">here</a>.
                </li>
                <li>
                    Create <a href="../data/database.sql">this database</a> in your
                    server, then <a href="../data/database_populate.sql">populate it</a>.
                </li>
                <li>
                    This example also provides provides <a href="database_populate_example.sql">some more data</a>
                    for you.
                </li>
                <li>
                    Configure the database options in <code>../config/medools.php</code>
                </li>
            </ol>
        </section>

        <section id="new_person">
            <h2>New Person</h2>
            <p>
                Add people to interact with the system. Your website should have
                a customer register page, and the administrator would manage
                the assignors.
            </p>
            <form action="new_person.php" method="POST">
                <input id="person_type_assignor" name="person_type" type="radio" value="assignor" onchange="element_enabled('person_type_assignor_fields', true)" checked />
                <label for="person_type_assignor">New Assignor</label>
                <br />
                <input id="person_type_customer" name="person_type" type="radio" value="customer" onchange="element_enabled('person_type_assignor_fields', false)" />
                <label for="person_type_customer">New Customer</label>
                <br />
                <br />
                <fieldset id="person_fields">
                    <div>Name:</div><input name="name" max="60" required /><br />
                    <div>Document:</div><input name="document" pattern="(\d{3}\.?\d{3}\.?\d{3}-?\d{2}|\d{2}\.?\d{3}\.?\d{3}/?\d{4}-?\d{2})" required />
                </fieldset>
                <fieldset id="address_fields">
                    <h3>Address</h3>
                    <div>Place:</div><input name="place" max="60" required /><br />
                    <div>Number:</div><input name="number" max="20" required /><br />
                    <div>Detail:</div><input name="detail" max="60" /><br />
                    <div>Neighborhood:</div><input name="neighborhood" max="60" required /><br />
                    <div>Zipcode:</div><input name="zipcode" pattern="\d{2}\.?\d{3}-?\d{3}" required /><br />
                    <div>Country:</div><select id="address_country"></select><br />
                    <div>State:</div><select id="address_state"></select><br />
                    <div>County:</div><select id="address_county" name="county"></select>
                </fieldset>
                <fieldset id="person_type_assignor_fields">
                    <h3>Assignor</h3>
                    <div>Bank:</div><select name="bank"></select><br />
                    <div>Wallet:</div><select name="wallet"></select><br />
                    <div>Covenant:</div><input name="covenant" max="20" pattern="\d{1,20}" required /><br />
                    <div>Agency:</div><input name="agency" max="5" pattern="\d{1,5}" required /><br />
                    <div>Agency check digit:</div><input name="agency_cd" max="1" pattern="\d" required /><br />
                    <div>Account:</div><input name="account" max="11" pattern="\d{1,11}" required /><br />
                    <div>Account check digit:</div><input name="account_cd" max="1" pattern="\d" required /><br />
                    <div>EDI:</div><input name="edi" max="6" pattern="\d{1,6}" required /><br />
                    <div>Logo:</div><input name="logo" max="30" /><br />
                    <div>URL:</div><input name="url" type="url" max="30" />
                </fieldset>
                <br />
                <button>Send</button>
            </form>
        </section>

        <section id="generate_title">
            <h2>Generate Title</h2>
            <p>
                When the customer buys something, this is what is happening.
            </p>
            <p>
                The client would log in, choose some products (the value below
                is the sum) and the server would known the assignor.
            </p>
            <form action="generate_title.php" method="POST">
                <table>
                    <tr>
                        <td>The customer</td>
                        <td>
                            <select name="payer" required>
<?php

protected_example('list_payers');

?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>is buying from</td>
                        <td>
                            <select name="assignor" required>
<?php

protected_example('list_assignors');

?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>something of <span>R$</span></td>
                        <td><input name="value" type="number" min="0.5" step="0.01" value="5" required /></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button name="action" value="generate_title">Ok</button></td>
                    </tr>
                </table>
            </form>
        </section>

        <section id="generate_shipping_file">
            <h2>Generate Shipping File</h2>
            <p>
                Below is a list of all titles in the database. Those not yet in a
                shipping file have a checkbox.
            </p>
            <form method="POST">
                <table class="table-list">
                    <tr>
                        <th><input type="checkbox" onchange="select_all(this, 'titles[]')" /></th>
                        <th>id</th>
                        <th>Client</th>
                        <th>Assignor</th>
                        <th>Value</th>
                        <th>Date</th>
                        <th>Billet</th>
                    </tr>
<?php

protected_example('list_titles');

?>
                </table>
                <button formaction="generate_shipping_file.php">Ok</button>
                <p>
                    Remember that, in production, you have to generate and send the
                    Shipping File before outputing the Billet.
                </p>
            </form>
        </section>

        <section id="generate_cnab">
            <h2>Generate CNAB</h2>
            <p>
                Below is a list of all Shipping Files in the database. Choose how you
                want to render them.
            </p>
            <table class="table-list">
                <tr>
                    <th>id</th>
                    <th>Titles</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>CNAB</th>
                </tr>
<?php

protected_example('list_shipping_files');

?>
            </table>
        </section>

        <section id="process_return_file">
            <h2>Return File</h2>
            <p>
                Enter a Return File sent by a Bank to process it
            </p>
            <form action="process_return_file.php" method="POST">
                <textarea name="return_file" required></textarea>
                <p>
                    <label><input name="apply" type="checkbox" />Apply in the Database</label>
                </p>
                <button>Send</button>
            </form>
        </section>
    </main>
</body>
</html>
