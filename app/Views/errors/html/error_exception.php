<?php ?>

<div class="ci-error-box">

    <h4>An uncaught Exception was encountered</h4>

    <p>Type: <?= get_class($exception) ?></p>
    <p>Message: <?= $message ?></p>
    <p>Filename: <?= $exception->getFile() ?></p>
    <p>Line Number: <?= $exception->getLine() ?></p>

    <?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>

        <p>Backtrace:</p>

        <?php foreach ($exception->getTrace() as $error): ?>

            <?php if (isset($error['file']) && strpos($error['file'], SYSTEMPATH) !== 0): ?>

                <p class="ci-error-indented">
                    File: <?= $error['file'] ?><br>
                    Line: <?= $error['line'] ?><br>
                    Function: <?= $error['function'] ?>
                </p>
            <?php endif ?>

        <?php endforeach ?>

    <?php endif ?>

</div>
