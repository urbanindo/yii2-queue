# Change Log
All notable changes to this project will be documented in this file.

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
