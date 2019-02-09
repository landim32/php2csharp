using PHP2CSharp.Core;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Text;
using System.Threading.Tasks;

namespace PHP2CSharp
{
    class Program
    {
        static void Main(string[] args)
        {
            /*
            var origFile = @"F:\xampp\htdocs\wooden-stone\server\src\DAL\LeilaoDAL.php";
            string origSource = File.ReadAllText(origFile);
            var php2csharp = new PHP2CSharpConverter();
            var destinySource = php2csharp.convert(origSource);
            Console.Write(destinySource);
            Console.ReadKey();
            */

            //try {
            var php2csharp = new PHP2CSharpConsole();
                php2csharp.OriginDir = @"F:\xampp\htdocs\wooden-stone\server\src";
                php2csharp.DestinyDir = @"F:\Projetos\Emagine.NET\Emagine.RPG";
                php2csharp.execute();
            //Console.ReadKey();
            /*
            }
            catch (Exception erro) {
                Console.WriteLine(erro.Message);
                Console.WriteLine(erro.StackTrace);
                Console.ReadKey();
            }
            */
        }
    }
}
