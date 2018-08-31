[@ZenHelp_bot](https://t.me/ZenHelp_Bot)

### ToDo List
- [ ] Clean up 
- [ ] How to mine
- [ ] /community: Discord twitter etc

## Usage
All commands associated with making a transaction will have a 0.0001 network fee. Keep that in mind when tipping or withdrawing.

#### Tipping
This bot enables users to send tips to users they find helpful.

To do this, the sending user firstly has to generate a deposit address by sending `/deposit` to the bot in private.
The user would then send ZEN to that address and wait until `/mybalance` shows the correct balance.

Once that balance has updated, the user can reply to the helpful message with `/tip` and the amount in ZEN behind it. (`/tip 0.01`)

If the user has enough funds, the bot will tell the helpful user in the group that they received a tip. If the funds are not enough (forgot to substract tip for example) nothing will happen.

#### Withdrawing
A user that received a tip can either use that tip to tip other users, or withdraw their tip to their own address. 

This can be done with `/myaddress` and `/withdraw`. Firstly, the user has to set their receiving address by using `/myaddress t-addr`.

They can then use `/withdraw` with the amount following to withdraw their balance.

#### Thanking
The ZenBot also has a small reputation system built in.
Users that received helpful advice can reply to the helping user with `/thanks` to increase their score.

Their own score can be seen by sending `/mythanks` to the bot in private. 

There also is a scoreboard which can be seen by sending `/scoreboard` to the bot.
The top 3 users with the most thanks qualify for a __special reward__ before the scoreboard is reset each month.

That reward is sent to their `/deposit`-address and can be withdrawn like a tip would (`/withdraw`).

#### Donations
ZEN zni7tRLevBnJxWMzkUoMVze1e6RCSPDdbfw

BTC 122WSgrn2YVG6KQSKB53jfCaZYi3xMi6nb

ETH 0x4b39187EBBb674Fb659A81a433D8e8AfbE3aA32b
