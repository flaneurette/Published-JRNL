# Published-JRNL

A simple journal parser with a `.jrnl` extension.

### Use

https://www.example.org/journal/retrieve.php?entry=test

### The markup is simple

Only these tags are parsed: P,U,B,L,I,S,H,E,D,A
```
P = Paragraph
U = Ul
B = Strong
L = List item
I = Image
S = Strike through
H = Headings from 1 to 6
E = Emphasized
D = Date time
A = Hyperlink
```
### Example

```
#jrnl v1 - 2026/11/25

#h1 Welcome to the journal!

#d 2026/11/25

#p This is Published JRNL v1.0

#a https://www.github.com

#l hello
#l second item
```
