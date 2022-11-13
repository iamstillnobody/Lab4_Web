<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName('Google Sheets in PHP');
$client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($client);

$spreadsheetId = "101Lp4fmJy_6iqpbvxVwrcdjYFr4SEmCYmOf0MXZeGCc";

// создаём заголовки (названия столбцов) для листа Bulletins
$rangeForHeaders = "Bulletins!A1:D1";
$valuesForHeaders = new Google_Service_Sheets_ValueRange();
$valuesForHeaders->setValues(["values" => ["email", "category", "title", "description"]]);
$optionsForHeaders = ["valueInputOption" => "RAW"];
$service->spreadsheets_values->update($spreadsheetId, $rangeForHeaders, $valuesForHeaders, $optionsForHeaders);
?>

<!-- форма для добавления новой категории -->
<html lang="en">
    <form action='table.php' method='post'>
        <label for="category">category:</label>
        <label>
            <input type='text' name='category' required>
        </label>

        <input type='submit' name='submit' value='Submit'>
    </form>
</html>

<!-- добавление новой категории в лист Categories -->
<?php
if (isset($_POST['submit'])) {
    $category = $_POST['category'];
    $rangeForANewCategory = "Categories!A1";
    $valuesForANewCategory = new Google_Service_Sheets_ValueRange([
        'values' => [[$category]]
    ]);

    // needle -- элемент для поиска, haystack -- массив, strict -- строгое/нестрогое сравнение (с учётом/без учёта типа)
    function in_2D_array($needle, $haystack, $strict = false) {
        foreach ($haystack as $item)
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_2D_array($needle, $item, $strict)))
                return true;
        return false;
    }

    $rangeForCategories = "Categories!A1:A";
    $response = $service->spreadsheets_values->get($spreadsheetId, $rangeForCategories);
    $categories = $response->getValues();

    // проверка, что предлагаемая для добавления категория не существует
    // если она уже существует, то она не будет добавлена в лист Categories
    if ($categories != null) {
        if (!in_2D_array($category, $categories)) {
            $optionsForANewCategory = ['valueInputOption' => 'RAW'];
            $service->spreadsheets_values->append($spreadsheetId, $rangeForANewCategory, $valuesForANewCategory, $optionsForANewCategory);
        }
    }
    else {
        $optionsForANewCategory = ['valueInputOption' => 'RAW'];
        $service->spreadsheets_values->append($spreadsheetId, $rangeForANewCategory, $valuesForANewCategory, $optionsForANewCategory);
    }
}
?>

<!-- форма для добавления нового объявления -->
<html lang="en">
    <form action='table.php' method='post'>
        <label>
            <select name='category' required>
                <?php
                $rangeForCategories = "Categories!A1:A";
                $response = $service->spreadsheets_values->get($spreadsheetId, $rangeForCategories);
                $categories = $response->getValues();
                if (count($categories) != 0)
                    foreach ($categories as $row) {
                        echo "<option value='$row[0]'>$row[0]</option>";
                    }
                ?>
            </select>
        </label>

        <label for="title">title:</label>
        <label>
            <input type='text' name='title' required>
        </label>

        <label for="description">description:</label>
        <label>
            <textarea rows="3" cols="25" name="description"></textarea>
        </label>

        <label for="email">email:</label>
        <label>
            <input type='text' name='email' required>
        </label>

        <input type='submit' name='bulletinSubmit' value='Submit'>
    </form>
</html>

<!-- добавление нового объявления в лист Bulletins -->
<?php
if (isset($_POST['bulletinSubmit']))
    if (true === isset($_POST['email'], $_POST['category'], $_POST['title'], $_POST['description'])) {
        $email = $_POST['email'];
        $category = $_POST['category'];
        $title = $_POST['title'];
        $description = $_POST['description'];

        $rangeForBulletinsToGet = "Bulletins!A2:D";
        $response = $service->spreadsheets_values->get($spreadsheetId, $rangeForBulletinsToGet);
        $categories = $response->getValues();

        if ($categories != null)
            $row = count($categories) + 1;
        else
            $row = 1;

        $rangeForANewBulletin = "Bulletins!A$row:D$row";
        $valuesForANewBulletin = new Google_Service_Sheets_ValueRange([
            'values' => [[$email, $category, $title, $description]]
        ]);
        $optionsForANewBulletin = ['valueInputOption' => 'RAW'];
        $service->spreadsheets_values->append($spreadsheetId, $rangeForANewBulletin, $valuesForANewBulletin, $optionsForANewBulletin);
}
?>

<!-- вывод таблицы объявлений на странице table.php -->
<?php
$rangeForBulletinsToGet = "Bulletins!A2:D";
$response = $service->spreadsheets_values->get($spreadsheetId, $rangeForBulletinsToGet);
$bulletins = $response->getValues();

if ($bulletins !== null)
    if (count($bulletins) != 0) {
        echo "<table>";
        echo "<thead>
                <th>email</th>
                <th>category</th>
                <th>title</th>
                <th>description</th>
            </thead>";
        echo "<tbody>";
        foreach ($bulletins as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }
?>

