# Changelog

## 1.2.0 (2018-07-09)

*   Feature: Forward compatiblity with EventLoop v0.5 and upcoming v1.0.
    (#28 by @clue)

*   Improve test suite by updating Travis config to test against legacy PHP 5.3 through PHP 7.2.
    (#27 by @clue)

*   Update project homepage.
    (#26 by @clue)

## 1.1.0 (2017-07-06)

*   Feature: Forward compatibility with Stream v1.0 and v0.7 (while keeping BC)
    (#22 by @Yoshi2889 and #23 by @WyriHaximus)

*   Improve test suite by fixing HHVM builds and ignoring future errors
    (#24 by @clue)

## 1.0.0 (2017-04-06)

*   First stable release, now following SemVer

    > Contains no other changes, so it's actually fully compatible with the v0.1 releases.

## 0.1.3 (2017-04-06)

*   Feature: Forward compatibility with Stream v0.6 and v0.5 (while keeping BC)
    (#18 and #20 by @clue)

*   Improve test suite by adding PHPUnit to require-dev
    (#19 by @clue)

## 0.1.2 (2016-06-14)

*   Fix: Fix processing events when input writes during data event
    (#15 by @clue)

*   Fix: Stop emitting events when closing stream during event handler
    (#16 by @clue)

*   Fix: Remove all event listeners when either stream closes
    (#17 by @clue)

## 0.1.1 (2016-06-13)

*   Fix: Continue parsing after a `c0` code in the middle of a stream
    (#13 by @clue)

*   Add more examples
    (#14 by @clue)

## 0.1.0 (2016-06-03)

*   First tagged release
