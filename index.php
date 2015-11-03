<?php

function calcAmountHours($desc)
{
    $lines = explode("\n", $desc);

    $amountHours = (float)0;
    foreach ($lines as $line) {
        if (substr($line, 0, 1) == '~') {
            if (preg_match_all('/([0-9\.\,]+)([m,h,d])/is', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $item) {
                    $number = (float)str_replace(',', '.', $item[1]);
                    $type  = $item[2];

                    if ($type == 'd') {
                        $amountHours += $number * 8;
                    }

                    if ($type == 'h') {
                        $amountHours += $number;
                    }

                    if ($type == 'm') {
                        $amountHours += round($number / 60, 2);
                    }
                }
            }
        }
    }

    return $amountHours;
}

if (isset($_FILES['json'])) {
    $json      = json_decode(file_get_contents($_FILES['json']['tmp_name']));
    $boardName = $json->name;
    $boardId   = $json->id;
    $cards     = isset($json->cards) ? $json->cards : array();
    $actions   = isset($json->actions) ? $json->actions : array();

    $cardNames = array();
    foreach ($cards as $card) {
        $cardNames[$card->idShort] = $card->name;
    }

    $workLog = array();
    foreach ($actions as $action) {
        if ($action->type != 'commentCard') {
            continue;
        }

        $amountHours = calcAmountHours($action->data->text);
        if ($amountHours == 0) {
            continue;
        }

        $cardId     = $action->data->card->idShort;
        $date       = new \DateTime($action->date);
        $numberWeek = $date->format('W');

        if (isset($workLog[$numberWeek][$cardId])) {
            $workLog[$numberWeek][$cardId] += $amountHours;
        } else {
            $workLog[$numberWeek][$cardId] = $amountHours;
        }
    }

    foreach ($workLog as $numberWeek => $workLogWeek) {
        $handler = fopen('reports/' . $boardName . '_week_' . $numberWeek . '.csv', 'w');
        $heads = array(
            'Сокр ID',
            'Затрачено часов',
            'Заголовок',
        );
        fputcsv($handler, $heads, ';');

        foreach ($workLogWeek as $cardId => $amountHours) {
            $data = array(
                $cardId,
                str_replace('.', ',', (string)$amountHours),
                $cardNames[$cardId],
            );
            fputcsv($handler, $data, ';');
        }
        fclose($handler);
    }
}
?>

<html>
    <body>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="json">
            <input type="submit">
        </form>
    </body>
</html>
