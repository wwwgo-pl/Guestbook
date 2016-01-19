<?php

$dsn = 'sqlite:guestbook.db';
$method = $_SERVER['REQUEST_METHOD'];

if ('POST' === $method && isset($_POST['gb_save'])) {
    $args = array(
        'gb_email' => array(
            'filter' => FILTER_VALIDATE_EMAIL
        ),
        'gb_username' => array(
            'filter'  => FILTER_CALLBACK,
            'options' => function($username) {
                return (mb_strlen($username) < 5) ? false : filter_var($username, FILTER_SANITIZE_STRING);
            }
        ),
        'gb_comment' => array(
            'filter'  => FILTER_CALLBACK,
            'options' => function($comment) {
                return (mb_strlen($comment) < 10) ? false : filter_var($comment, FILTER_SANITIZE_STRING);
            }
        )
    );
    $filter = filter_input_array(INPUT_POST, $args);
    var_dump(array_filter($filter));
    // TODO: przerwać działanie skryptu jeżeli część pól została niepoprawnie wypełniona
    
    try {
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA encoding = "UTF-8";');

        $sql = 'INSERT INTO guestbook(email, username, comment) VALUES(:email, :username, :comment)';
        $sth = $dbh->prepare($sql);
        $sth->bindParam(':email', $filter['gb_email']);
        $sth->bindParam(':username', $filter['gb_username']);
        $sth->bindParam(':comment', $filter['gb_comment']);
        $sth->execute();
    } catch (PDOException $e) {
        echo 'Klasa PDO zwróciła wyjątek: ' . $e->getMessage();
    }
} elseif ('GET' === $method) {
    try {
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA encoding = "UTF-8";');

        $sql = 'SELECT * FROM guestbook';
        $result = $dbh->query($sql);
        if (false === $result) {
            echo 'Brak wpisów w księdze gości';
        } else {
            echo '<table class="gb_table">';
            echo '<thead><tr><th>E-mail</th><th>Nazwa użytkownika</th><th>Komentarz</th></tr></thead><tbody>';
            foreach ($result as $row) {
                echo '<tr>';
                echo '<td>', $row['email'], '</td>';
                echo '<td>', $row['username'], '</td>';
                echo '<td>', nl2br($row['comment']), '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    } catch (PDOException $e) {
        echo 'Klasa PDO zwróciła wyjątek: ' . $e->getMessage();
    }
}
require 'guestbook.html';
