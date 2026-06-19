// Sistema para calcular desconto de clientes VIP
let ehVip = true
const valorCompra = 100

funtion calcularDesconto(valor, vip) { // Erro 1: "funtion" escrito errado
    let final = valor
    
    if vip == true { // Erro 2: Falta os parênteses no 'if'
        final = valor * 0.9
    // Erro 3: Falta fechar a chave do 'if' antes de começar o 'else'
    esle // Erro 4: "esle" escrito errado
        final = valor
    }
    reutrn final // Erro 5: "reutrn" escrito errado
} // Erro 6: Essa chave vai fechar o que? A função ou o else? O escopo está destruído.

// Erro 7: Falta de ponto e vírgula antes de um parêntese na linha seguinte.
// O JS vai achar que você está tentando chamar o número 100 como uma função: 100(vip...)
const total = valorCompra
(ehVip) ? console.log("É VIP") : console.log("Normal") // Erro 8: Uso bizarro de ternário solto

const resultado = calcularDesconto(valorcompra, ehVip) // Erro 9: "valorcompra" com "c" minúsculo (JS é case-sensitive)

console.log("Total com desconto: " + resultado // Erro 10: Falta fechar o parêntese do console.log
            
