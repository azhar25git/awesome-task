<?php
session_start();

require __DIR__ . '/database.php';

$db = new Database();

// Handle form submission
if (isset($_POST['url'])) {
    $url = $_POST['url'];

    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $_SESSION['error'] = 'Invalid URL format';
    } else {
        if($_POST['formId'] != $_SESSION['formId'] ) {
            $shortCode = $db->shortenUrl($url);
            $newUrl = $_SERVER['HTTP_HOST'] . '/?code=' . $shortCode;
            $_SESSION['success'] = "Short URL created: <a href='/?code=$shortCode' target='_blank' id='text-to-copy'>$newUrl</a>";

            $_SESSION['formId'] = $_POST['formId'];
        } else {
            header('Location: /'); // homepage redirect on form resubmit
        }
    }
}

// Handle short URL redirection
if (isset($_GET['code'])) {
    $shortCode = $_GET['code'];
    $row = $db->getOriginalUrl($shortCode);
    if (isset($row['id'])) {
        $db->logClick($row['id']);
        header('Location: ' . $row['original_url']);
        exit;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'Short URL not found';
        exit;
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>URL Shortener</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
</head>

<body class="container-fluid vh-100">
    <section class="col-10 mx-auto my-4 text-center">
        <div class="row">
            <?php if (isset($_SESSION['error'])) : ?>
                <div class="p-3 border border-danger rounded"><?php echo $_SESSION['error'];
                                                                unset($_SESSION['error']); ?></div>
            <?php elseif (isset($_SESSION['success'])) : ?>
                <div class="p-3 border border-success rounded"><?php echo $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                    <div class="d-inline-block float-end">
                        <button class="btn btn-sm text-success-emphasis float-end" id="copy-button">copy url</button>
                    </div>
                </div>
        </div>
    <?php endif; ?>
    </section>
    <section class="col-10 mx-auto my-4">
        <form method="post">
            <input type="hidden" name="formId" value = "<?php echo uniqid(); ?>">
            <div class="row">
                <div class="col-2">
                    <label for="url" class="form-label my-0 py-2"> <strong>Enter a URL to shorten:</strong></label>
                </div>
                <div class="col-4">
                    <input type="url" name="url" class="form-control" id="url" required>
                </div>
                <div class="col-1">
                    <button type="submit" class="btn btn-outline-dark">Shorten</button>
                </div>

            </div>
        </form>

    </section>
    <section class="col-10 mx-auto">
        <h4>Link Clicks</h4>
        <table id="url-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th class="col-6">Original URL</th>
                    <th>Short URL</th>
                    <th>Number of Clicks</th>
                    <th>Number of Unique Clicks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $urls = $db->getShortenedUrls();

                foreach ($urls as $url) {
                    echo "<tr>";
                    echo "<td>{$url['id']}</td>";
                    echo "<td>{$url['original_url']}</td>";
                    echo "<td>{$_SERVER['HTTP_HOST']}/?code={$url['short_code']}</td>";
                    echo "<td>{$url['clicks']}</td>";
                    echo "<td>{$url['unique_clicks']}</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </section>
    <footer class="mt-5">
        <div class="row justify-center">
            <p class="text-center">Copyright &copy; <a href="https://github.com/azhar25git" target="_blank" >Azhar Uddin</a> <?php echo date('Y');?></p>
        </div>
    </footer>

    <script>
        const copyButton = document.getElementById('copy-button');
        const textToCopy = document.getElementById('text-to-copy');

        copyButton.addEventListener('click', () => {
            navigator.clipboard.writeText(textToCopy.textContent)
                .then(() => {
                    console.log('URL copied to clipboard!');

                    alert('Copied URL!');
                })
                .catch(err => {
                    console.error('Failed to copy URL: ', err);
                });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        new DataTable('#url-table', {
            order: [[0, 'desc']]
        });
    </script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script> -->
</body>

</html>