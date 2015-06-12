# Change Log
All notable changes to this project will be documented in this file.

## 1.2.1
- Added `DeferredEventTrait`

## 1.2.0
- Added tests
- Added `MemoryQueue`, `DeferredEventHandler`, and `ActiveRecordDeferredEventHandler`.

## 1.0.1

### Changed
- Refactoring controller classes to Web, Console, and Worker.

### Added
- Added Web endpoint for posting queue.

## 2015-02-25

### Changed
- Shorten  `postJob`, `getJob`, `deleteJob`, `runJob` method name to `post`, 
  `fetch`, `delete`, `run`.

### Fixed
- Error when closure is not returning boolean variable.

### Added
- DeferredEventBehavior for deferring event handler to the task queue.
- Peek and Purging in the console command.
- MultipleQueue for multiple queue and priority queue.
