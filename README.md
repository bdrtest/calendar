# Retreat Availability Calendar Coding Project

Please note that security.php must be updated with appropriate database credentials and static tokens.

# Test Plan

- Point control at test database
	- Test database will have data filled in that draws simple pictures using the calendar days as pixel art
	- Test database will have hover registrations counting up sequentially or alternating some pattern
	- Test database will have documented number of available days in each month
- Verify months match test pattern in database in both reserved and pending notification modes
- Hover and verify registrations count up sequentially
- Verify available days matches test database documentation
- Click and edit 3 cells and deselect/remove all booking information
	- Reload page
	- Verify 3 cells have no booking information
- Click and edit 3 cells and select every checkbox, select 2nd option in combobox, enter Test 1~2!3@4#5$6%7^8&9*0(1)2-3=4+5'6"7` for flight information 
	- Reload page
	- Verify 3 cells have correct additional information
