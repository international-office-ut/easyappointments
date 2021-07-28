<select <?= $attributes ?>>
    <?php foreach ($timezones as $continent => $entries): ?>
        <optgroup label="<?= $continent ?>">
            <?php foreach ($entries as $value => $name): ?>
                <?php if ($value == date_default_timezone_get()): ?>
                    <option selected value="<?= $value ?>"><?= $name ?></option>
                <?php else:?>
                    <option value="<?= $value ?>"><?= $name ?></option>
                <?php endif ?>
            <?php endforeach ?>
        </optgroup>
    <?php endforeach ?>
</select>
