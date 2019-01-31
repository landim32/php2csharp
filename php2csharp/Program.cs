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
            string origFile = @"F:\Projetos\php2csharp\teste\AcaoInfo.php";
            string origSource = File.ReadAllText(origFile);
            var php2csharp = new PHP2CSharpConverter();
            var destinySource = php2csharp.convert(origSource);
            Console.Write(destinySource);
            Console.ReadKey();
        }
    }
}
