<?php

if (isset($_FILES['json'])) {
    $json    = json_decode(file_get_contents($_FILES['json']['tmp_name']));
    $cards   = isset($json->cards) ? $json->cards : array();
    $members = isset($json->members) ? $json->members : array();

    $handler = fopen('data_list.csv', 'w');
    $heads = array(
        'Сокр ID',
        'Заголовок',
        'Описание',
        'Время посл. ак.',
        'Участники',
        'ID',
    );
    fputcsv($handler, $heads, ';');

    $listNames = array();
    foreach ($json->lists as $list) {
        $listNames[$list->id] = $list->name;
    }


    foreach ($cards as $card) {

        if (!empty($_POST['list_name'])) {
            if ($listNames[$card->idList] != $_POST['list_name']) {
                continue;
            }
        }

        if ($card->closed) {
            continue;
        }

        $data = array(
            $card->idShort,
            $card->name,
            $card->desc,
            $card->dateLastActivity,
            getMembersToString($card->idMembers, $members),
            $card->id,
        );

        fputcsv($handler, $data, ';');
    }

    fclose($handler);
}

function getMembersToString($idMembers, $members)
{
    $idMembers = array_flip($idMembers);
    $memberNames = array();
    foreach ($members as $member) {
        if (!isset($idMembers[$member->id])) {
            continue;
        }

        $memberNames[] = $member->username;
    }

    return implode(', ', $memberNames);
}

?>
<html>
    <body>
        <h1>Get task list</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="json">
            name of list:
            <input type="text" name="list_name" value="<?php echo isset($_POST['list_name']) ? $_POST['list_name'] : ''; ?>">
            <input type="submit">
        </form>
    </body>
</html>
